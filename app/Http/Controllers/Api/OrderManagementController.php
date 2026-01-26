<?php

namespace App\Http\Controllers\Api;

use App\Enum\Orderstatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status'    => [new Enum(Orderstatus::class)],
            'from_date' => 'date',
            'to_date'   => 'date|after_or_equal:from_date',
        ]);

        $query = Order::with(['user', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'orders' => $orders,
            'available_status' => Orderstatus::values(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load([
            'user',
            'items.product',
            'statusHistory.changedBy',
        ]);

        return response()->json([
            'order' => $order,
            'available_transitions' => $order->getAvilableTransitions(),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['required', new Enum(Orderstatus::class)],
            'notes'  => 'nullable|string|max:500',
        ]);

        try {
            $newStatus = Orderstatus::from($request->status);

            if (! $order->transitionTo($newStatus, Auth::user(), $request->notes)) {
                throw new Exception('Invalid status transition');
            }

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$newStatus->value}",
                'order'   => $order->fresh(['statusHistory.changedBy']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            if (! $order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be cancelled in its current status.',
                ], 400);
            }

            $order->transitionTo(
                Orderstatus::CANCELLED,
                Auth::user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Order has been cancelled',
                'order'   => $order->fresh(['statusHistory.changedBy']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
