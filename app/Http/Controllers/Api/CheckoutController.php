<?php

namespace App\Http\Controllers\Api;

use App\Enum\Orderstatus;
use App\Enum\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // 1️⃣ Validate request data
        $request->validate([
            'shipping_name'     => 'required|string|max:255',
            'shipping_address'  => 'required|string|max:255',
            'shipping_city'     => 'required|string|max:255',
            'shipping_state'    => 'nullable|string|max:255',
            'shipping_zipcode'  => 'required|string|max:20',
            'shipping_country'  => 'required|string|max:255',
            'shipping_phone'    => 'required|string|max:20',
            'payment_method'    => 'nullable|in:credit_card,paypal,cod',
            'notes'             => 'nullable|string',
        ]);

        $user = $request->user();

        // 2️⃣ Get cart items
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }

        $subtotal = 0;
        $orderItems = [];

        // 3️⃣ Validate products and calculate subtotal
        foreach ($cartItems as $item) {
            $product = $item->product;

            if (!$product || !$product->is_active) {
                return response()->json([
                    'message' => 'Product is not available'
                ], 400);
            }


            if ($product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Not enough stock for product '{$product->name}'"
                ], 400);
            }

            $itemSubtotal = round($product->price * $item->quantity, 2);
            $subtotal += $itemSubtotal;

            $orderItems[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product_sku'  => $product->sku,
                'quantity'     => $item->quantity,
                'price'        => $product->price,
                'subtotal'     => $itemSubtotal,
            ];
        }

        // 4️⃣ Calculate totals
        $tax = round($subtotal * 0.08, 2);
        $shippingCost = 5.00;
        $total = round($subtotal + $tax + $shippingCost, 2);

        // 5️⃣ Database transaction
        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id'          => $user->id,
                'order_number'     => Order::generateOrderNumber(),
                'status'           => Orderstatus::PENDING,
                'payment_method'   => $request->payment_method ?? 'cod',
                'payment_status'   => PaymentStatus::PENDING,
                'shipping_name'    => $request->shipping_name,
                'shipping_address' => $request->shipping_address,
                'shipping_city'    => $request->shipping_city,
                'shipping_state'   => $request->shipping_state,
                'shipping_zipcode' => $request->shipping_zipcode,
                'shipping_country' => $request->shipping_country,
                'shipping_phone'   => $request->shipping_phone,

                'subtotal'         => $subtotal,
                'tax'              => $tax,
                'shipping_cost'    => $shippingCost,
                'total'            => $total,

                'notes'            => $request->notes,
            ]);

            // Save order items & reduce stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'], 
                    'product_sku'  => $item['product_sku'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'subtotal'   => $item['subtotal'],
                ]);

                // Reduce product stock
                $product = $cartItems
                    ->firstWhere('product_id', $item['product_id'])
                    ->product;

                $product->decrement('stock', $item['quantity']);
            }

            // Clear cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $total
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function OrderHistory(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->with('items')->get();

        return response()->json([
            'message' => 'Order history retrieved successfully',
            'orders' => $orders,
            'status' => true,
        ]);
    }
    public function OrderDetails(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->with('items')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found', 'status' => false], 404);
        }
        return response()->json([
            'message' => 'Order details retrieved successfully',
            'orders' => $order,
            'status' => true,
        ]);
    }
}
