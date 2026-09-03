<?php

namespace App\Http\Controllers;

use App\Events\BarTicketUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BarStockService;
use Illuminate\Http\Request;

class BarController extends Controller
{
    public function __construct(private readonly BarStockService $barStock) {}

    private function authorizeOrderItem(Request $request, OrderItem $item): bool
    {
        $order = $item->order;

        return $order
            && $order->organization_id === $request->user()->organization_id
            && $order->branch_id === $request->user()->branch_id;
    }

    // Fetch active bar tickets
    public function index(Request $request)
    {
        $orders = Order::where('branch_id', $request->user()->branch_id)
            ->whereHas('items', function ($query) {
                $query->whereHas('product', function ($pQuery) {
                    $pQuery->whereIn('type', ['drink', 'cocktail', 'bottle', 'shot']);
                })
                    ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
                    ->whereNull('voided_at');
            })
            ->with([
                'table:id,name',
                'waiter:id,name',
                'items' => function ($query) {
                    $query->whereHas('product', function ($pQuery) {
                        $pQuery->whereIn('type', ['drink', 'cocktail', 'bottle', 'shot']);
                    })
                        ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
                        ->whereNull('voided_at')
                        ->select(['id', 'order_id', 'product_name', 'variant_name', 'quantity', 'status', 'notes', 'serve_notes', 'modifiers', 'created_at']);
                },
            ])
            ->select(['id', 'table_id', 'order_number', 'status', 'opened_by', 'waiter_id', 'opened_at', 'sent_at'])
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json(['data' => $orders]);
    }

    // Update individual order item status
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        if (! $this->authorizeOrderItem($request, $item)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:PREPARING,READY,SERVED',
        ]);

        $wasServed = $item->status === 'SERVED';

        $item->update(['status' => $validated['status']]);

        // Bottle served → consume from bar inventory.
        if ($validated['status'] === 'SERVED' && ! $wasServed) {
            $this->barStock->consumeServedItem($item, $request->user()->id);
        }

        event(new BarTicketUpdated($item->order));

        return response()->json(['data' => $item]);
    }

    // PATCH /bar/items/{item}/serve-notes — note for the floor staff collecting the drink
    public function updateServeNotes(Request $request, OrderItem $item)
    {
        if (! $this->authorizeOrderItem($request, $item)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'serve_notes' => 'nullable|string|max:500',
        ]);

        $item->update(['serve_notes' => $validated['serve_notes'] ?? null]);

        event(new BarTicketUpdated($item->order));

        return response()->json(['data' => $item]);
    }

    // Update entire order status (e.g., mark all drink items as READY)
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
                $p->whereIn('type', ['drink', 'cocktail', 'bottle', 'shot']);
            })
            ->whereIn('status', ['SENT', 'PREPARING', 'READY'])
            ->whereNull('voided_at')
            ->update(['status' => $validated['status']]);

        // Whole ticket handed over → one pass over the bottles it consumed.
        if ($validated['status'] === 'SERVED') {
            $this->barStock->consumeServedItems($order, $request->user()->id);
        }

        event(new BarTicketUpdated($order));

        return response()->json(['message' => 'Order items updated successfully']);
    }
}
