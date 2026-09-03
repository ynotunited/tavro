<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function getTaxRate(Request $request): float
    {
        $org = Organization::find($request->user()->organization_id);
        return (float) ($org->tax_percentage ?? 0);
    }

    private function getScRate(Request $request): float
    {
        $org = Organization::find($request->user()->organization_id);
        return (float) ($org->service_charge_percentage ?? 0);
    }

    private function authorizeOrder(Request $request, Order $order): bool
    {
        return $order->organization_id === $request->user()->organization_id
            && $order->branch_id === $request->user()->branch_id;
    }

    private function authorizeOrderItem(Request $request, Order $order, OrderItem $item): bool
    {
        return $item->order_id === $order->id
            && $order->organization_id === $request->user()->organization_id
            && $order->branch_id === $request->user()->branch_id;
    }

    // GET /orders — active orders for this branch
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'   => 'nullable|in:OPEN,SENT,PREPARING,READY,SERVED,CLOSED,PAID,VOIDED',
            'table_id' => 'nullable|integer|exists:tables,id',
        ]);

        $orders = Order::where('branch_id', $request->user()->branch_id)
            ->with(['table', 'waiter', 'items'])
            ->when($validated['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($validated['table_id'] ?? null, fn($q, $t) => $q->where('table_id', $t))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $orders]);
    }

    // GET /orders/{id}
    public function show(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->load(['table', 'waiter', 'openedBy', 'items.product', 'items.variant']);
        return response()->json(['data' => $order]);
    }

    // POST /orders — open a new order on a table
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id'     => 'nullable|exists:tables,id',
            'cover_count'  => 'nullable|integer|min:1',
            'waiter_id'    => 'nullable|exists:users,id',
        ]);

        $activeShift = \App\Models\Shift::where('user_id', $request->user()->id)
            ->whereIn('status', ['OPEN', 'CLOSING'])
            ->first();

        $branchId = $request->user()->branch_id;

        if (!$branchId) {
            return response()->json([
                'message' => 'You are not assigned to any branch. Ask your organization admin to assign you before opening orders.',
            ], 422);
        }

        $order = Order::create([
            'organization_id' => $request->user()->organization_id,
            'branch_id'       => $branchId,
            'shift_id'        => $activeShift ? $activeShift->id : null,
            'table_id'        => $validated['table_id'] ?? null,
            'cover_count'     => $validated['cover_count'] ?? 1,
            'waiter_id'       => $validated['waiter_id'] ?? null,
            'opened_by'       => $request->user()->id,
            'order_number'    => Order::generateOrderNumber($branchId),
            'status'          => 'OPEN',
            'opened_at'       => now(),
        ]);

        if (isset($validated['table_id'])) {
            Table::where('id', $validated['table_id'])->update(['status' => 'occupied']);
        }

        return response()->json(['data' => $order->load(['table', 'waiter', 'items'])], 201);
    }

    // POST /orders/{id}/items — add item to order
    public function addItem(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'product_id'         => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity'           => 'required|integer|min:1|max:999',
            'notes'              => 'nullable|string|max:500',
            'modifiers'          => 'nullable|array|max:10',
            'modifiers.*'        => 'string|max:100',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->organization_id !== $order->organization_id) {
            return response()->json(['message' => 'Product does not belong to your organization'], 403);
        }

        $price = $product->selling_price;

        if (!empty($validated['product_variant_id'])) {
            $variant = $product->variants()->find($validated['product_variant_id']);
            if ($variant) {
                $price = $variant->selling_price ?? $price;
            }
        }

        $qty = $validated['quantity'] ?? 1;

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $product->id,
            'product_variant_id' => $validated['product_variant_id'] ?? null,
            'product_name'       => $product->name,
            'variant_name'       => isset($variant) ? $variant->name : null,
            'unit_price'         => $price,
            'quantity'           => $qty,
            'subtotal'           => $price * $qty,
            'is_taxable'         => $product->is_taxable,
            'has_service_charge' => $product->has_service_charge,
            'notes'              => $validated['notes'] ?? null,
            'modifiers'          => $validated['modifiers'] ?? null,
            'status'             => 'PENDING',
        ]);

        $order->recalculate($this->getTaxRate($request), $this->getScRate($request));

        return response()->json(['data' => $item], 201);
    }

    // PATCH /orders/{id}/items/{itemId} — update quantity or notes
    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        if (!$this->authorizeOrderItem($request, $order, $item)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'notes'    => 'nullable|string',
        ]);

        if (isset($validated['quantity'])) {
            $validated['subtotal'] = $item->unit_price * $validated['quantity'];
        }

        $item->update($validated);
        $order->recalculate($this->getTaxRate($request), $this->getScRate($request));

        return response()->json(['data' => $item]);
    }

    // DELETE /orders/{id}/items/{itemId} — void an item
    public function voidItem(Request $request, Order $order, OrderItem $item)
    {
        if (!$this->authorizeOrderItem($request, $order, $item)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        $item->update([
            'status'      => 'VOIDED',
            'void_reason' => $validated['void_reason'],
            'voided_by'   => $request->user()->id,
            'voided_at'   => now(),
        ]);

        $order->recalculate($this->getTaxRate($request), $this->getScRate($request));

        return response()->json(['message' => 'Item voided']);
    }

    // POST /orders/{id}/send — send to kitchen
    public function send(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $pendingItems = $order->items()->where('status', 'PENDING')->get();
        $inventoryService = new \App\Services\InventoryService();

        foreach ($pendingItems as $item) {
            $item->update(['status' => 'SENT']);
            $product = \App\Models\Product::find($item->product_id);
            if ($product) {
                $inventoryService->deductFromRecipe($product, $item->quantity, $order, $request->user()->id);
            }
        }
        
        $order->update(['status' => 'SENT', 'sent_at' => now()]);

        event(new \App\Events\KitchenTicketUpdated($order));
        event(new \App\Events\BarTicketUpdated($order));

        return response()->json(['data' => $order->fresh()]);
    }

    // POST /orders/{id}/void — void the whole order
    public function void(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        $order->update([
            'status'      => 'VOIDED',
            'void_reason' => $validated['void_reason'],
            'voided_by'   => $request->user()->id,
            'voided_at'   => now(),
        ]);

        if ($order->table_id) {
            $order->table->update(['status' => 'AVAILABLE']);
        }

        return response()->json(['data' => $order->fresh()]);
    }

    // POST /orders/{id}/discount — apply a discount
    public function applyDiscount(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'discount_type'  => 'required|in:percent,flat',
            'discount_value' => 'required|numeric|min:0|max:100000',
        ]);

        if ($validated['discount_type'] === 'percent' && $validated['discount_value'] > 100) {
            abort(422, 'Percentage discount cannot exceed 100%.');
        }

        $order->update($validated);
        $order->recalculate($this->getTaxRate($request), $this->getScRate($request));

        return response()->json(['data' => $order->fresh()]);
    }

    // POST /orders/{id}/close
    public function close(Request $request, Order $order)
    {
        if (!$this->authorizeOrder($request, $order)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->update(['status' => 'CLOSED', 'closed_at' => now()]);

        if ($order->table_id) {
            $order->table->update(['status' => 'AVAILABLE']);
        }

        return response()->json(['data' => $order->fresh()]);
    }
}
