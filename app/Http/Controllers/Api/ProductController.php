<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $products = Product::paginate(10);
        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:255|unique:products',
            'is_active' => 'boolean'
        ]);
    $data['slug'] = Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => $product
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
{
    $data = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'description' => 'sometimes|nullable|string',
        'price' => 'sometimes|required|numeric|min:0',
        'stock' => 'sometimes|required|integer|min:0',
        'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $product->id,
        'is_active' => 'sometimes|boolean'
    ]);

    if (isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }

    $product->update($data);

    return response()->json([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => $product
    ], 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
        $product->delete();
        return response()->json([
            'success'=>true,
            'message'=>'Product deleted successfuly'
        ],200);
    }
}
