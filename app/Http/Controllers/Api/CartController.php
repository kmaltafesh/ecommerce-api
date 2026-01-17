<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * عرض منتجات السلة للمستخدم الحالي.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // استخدام Eager Loading لتحسين الأداء (N+1 Problem)
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cart items retrieved successfully',
            'cart' => $cartItems,
            'total' => $total
        ]);
    }

    /**
     * إضافة منتج للسلة أو تحديث الكمية إذا كان موجوداً.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'product_id' => 'required|exists:products,id', // تأكد من اسم الجدول (غالباً products)
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $data['product_id'])
                        ->first();

        if ($cartItem) {
            $cartItem->quantity += $data['quantity'];
            $cartItem->save();
            $status = 200;
            $msg = 'Cart item quantity updated';
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
            ]);
            $status = 201;
            $msg = 'Product added to cart';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'cart' => $cartItem,
        ], $status);
    }

    /**
     * تحديث كمية منتج معين في السلة.
     */
    public function update(Request $request, Cart $cart)
    {
        // حماية: التأكد أن السلة تخص المستخدم الذي يحاول التعديل
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart->quantity = $data['quantity'];
        $cart->save(); // تأكد من وجود الأقواس

        return response()->json([
            'success' => true,
            'message' => 'Cart quantity updated successfully',
            'cart' => $cart,
        ], 200);
    }

    /**
     * حذف منتج من السلة.
     */
    public function destroy(Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ], 200);
    }
}