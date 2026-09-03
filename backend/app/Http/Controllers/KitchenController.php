<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    private function authorizeOrderItem(Request $request, OrderItem $item): bool
    {
        $order = $item->order;
        return $order
            && $order->organization_id === $request->user()->organization_id
            && $order->branch_id === $request->user()->branch_id;
    }

    // Fetch active kitchen tickets
    public function index(Request $request)
    {
        $orders = Order::where('branch_id', $request->user()->branch_id)
            ->whereHas('items', function ($query) {
                $query->whereHas('product', function ($pQuery) {
                    $pQuery->where('type', 'food');
                })
                ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
                ->whereNull('voided_at');
            })
            ->with(['table', 'items' => function ($query) {
                $query->whereHas('product', function ($pQuery) {
                    $pQuery->where('type', 'food');
                })
                ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
                ->whereNull('voided_at')
                ->select(['id', 'order_id', 'product_name', 'variant_name', 'quantity', 'status', 'notes', 'modifiers', 'created_at']);
            }])
            ->select(['id', 'table_id', 'order_number', 'status', 'opened_by', 'waiter_id', 'opened_at', 'sent_at'])
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json(['data' => $orders]);
    }

    // Update individual order item status
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        if (!$this->authorizeOrderItem($request, $item)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:PREPARING,READY,SERVED',
        ]);

        $item->update(['status' => $validated['status']]);
        
        event(new \App\Events\KitchenTicketUpdated($item->order));

        return response()->json(['data' => $item]);
    }

    // Update entire order status (e.g., mark all food items as READY)
    public function updateOrderStatus(Request $request, Order $order)
    {
        if ($order->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:PREPARING,READY,SERVED',
        ]);

        $order->items()
            ->whereHas('product', function ($p) {
                $p->where('type', 'food');
            })
            ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
            ->whereNull('voided_at')
            ->update(['status' => $validated['status']]);

        event(new \App\Events\KitchenTicketUpdated($order));

        return response()->json(['message' => 'Order items updated successfully']);
    }
}
