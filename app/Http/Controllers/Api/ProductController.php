<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /* ================== Constants ================== */
    private const CACHE_TTL = 300; // 5 minutes
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;
    private const MAX_IMAGE_SIZE = 2048; // KB

    /* ================== Index ================== */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min(
                (int) $request->input('per_page', self::DEFAULT_PER_PAGE),
                self::MAX_PER_PAGE
            );

            $page = (int) $request->input('page', 1);

            $cacheKey = "products:index:page_{$page}:per_{$perPage}";

            $products = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                fn() => Product::withoutTrashed()
                    ->with('categories')
                    ->paginate($perPage)
            );

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load products', 500, $e);
        }
    }

    /* ================== Show ================== */
    public function show(Product $product): JsonResponse
    {
        try {
            $cacheKey = "product:{$product->id}";

            $product = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                fn() => $product->load('categories')
            );

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load product', 500, $e);
        }
    }

    /* ================== Store ================== */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'sku' => 'required|string|max:255|unique:products',
                'is_active' => 'boolean',
                'categories' => 'array',
                'categories.*' => 'exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:' . self::MAX_IMAGE_SIZE,
            ]);

            $data['slug'] = $this->generateSlug($data['name']);

            if ($request->hasFile('image')) {
                $data['image'] = $this->storeImage($request->file('image'), $data['slug']);
            }

            $product = Product::create($data);

            if (!empty($data['categories'])) {
                $product->categories()->attach($data['categories']);
            }

            $this->clearAllProductCache();

            return response()->json([
                'success' => true,
                'data' => $product->load('categories')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product', 500, $e);
        }
    }

    /* ================== Update ================== */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $product->id,
                'is_active' => 'sometimes|boolean',
                'categories' => 'sometimes|array',
                'categories.*' => 'exists:categories,id',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:' . self::MAX_IMAGE_SIZE,
            ]);

            if ($request->has('categories')) {
                $product->categories()->sync($request->categories);
            }

            if ($request->hasFile('image')) {
                $this->deleteImage($product->image);
                $data['image'] = $this->storeImage($request->file('image'), $product->slug);
            }

            $product->update($data);

            $this->clearProductCache($product->id);

            return response()->json([
                'success' => true,
                'data' => $product->load('categories')
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product', 500, $e);
        }
    }

    /* ================== Delete ================== */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->deleteImage($product->image);
            $product->delete();

            $this->clearProductCache($product->id);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product', 500, $e);
        }
    }

    /* ================== Restore ================== */
    public function restore(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        $this->clearAllProductCache();

        return response()->json(['success' => true]);
    }

    /* ================== Filter ================== */
    public function filter(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->input('page', 1);
            $cacheKey = 'products:filter:' . md5(json_encode($request->all())) . ":{$page}";

            $products = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($request) {
                    return Product::withoutTrashed()
                        ->when($request->price_min, fn($q) => $q->where('price', '>=', $request->price_min))
                        ->when($request->price_max, fn($q) => $q->where('price', '<=', $request->price_max))
                        ->when($request->q, function ($q) use ($request) {
                            $q->where('name', 'like', "%{$request->q}%")
                                ->orWhere('description', 'like', "%{$request->q}%");
                        })
                        ->latest()
                        ->paginate(self::DEFAULT_PER_PAGE);
                }
            );

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Filter failed', 500, $e);
        }
    }

    /* ================== Helpers ================== */
    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function storeImage($file, string $slug): string
    {
        return $file->storeAs('products', "{$slug}.{$file->extension()}", 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function clearProductCache(int $id): void
    {
        Cache::forget("product:{$id}");
        $this->clearAllProductCache();
    }

    private function clearAllProductCache(): void
    {
        // يعتمد على TTL أو Cache Tags في Redis
    }

    private function errorResponse(string $message, int $code, \Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], $code);
    }
}
