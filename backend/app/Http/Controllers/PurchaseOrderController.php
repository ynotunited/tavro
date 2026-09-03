<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PurchaseOrder::where('branch_id', $request->user()->branch_id)
            ->with(['supplier', 'orderedBy', 'items.inventoryItem'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference'   => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.qty_ordered'       => 'required|numeric|min:0.001',
            'items.*.unit_cost'         => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $totalCost = collect($validated['items'])->sum(fn($i) => $i['qty_ordered'] * $i['unit_cost']);

            $po = PurchaseOrder::create([
                'branch_id'   => $request->user()->branch_id,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'reference'   => $validated['reference'] ?? null,
                'notes'       => $validated['notes'] ?? null,
                'total_cost'  => $totalCost,
                'ordered_by'  => $request->user()->id,
                'status'      => 'DRAFT',
            ]);

            foreach ($validated['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'inventory_item_id'  => $itemData['inventory_item_id'],
                    'qty_ordered'        => $itemData['qty_ordered'],
                    'unit_cost'          => $itemData['unit_cost'],
                ]);
            }

            return response()->json(['data' => $po->load('items.inventoryItem')], 201);
        });
    }

    // Mark a PO as received and update stock
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($purchaseOrder->status === 'RECEIVED') {
            return response()->json(['message' => 'Purchase order already received.'], 400);
        }

        $validated = $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_received'     => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $purchaseOrder, $request) {
            $service = new InventoryService();

            foreach ($validated['items'] as $receivedLine) {
                $poItem = PurchaseOrderItem::findOrFail($receivedLine['purchase_order_item_id']);

                // Verify the PO item belongs to this purchase order
                if ($poItem->purchase_order_id !== $purchaseOrder->id) {
                    abort(403, 'Purchase order item does not belong to this order');
                }

                $poItem->update(['qty_received' => $receivedLine['qty_received']]);

                $inventoryItem = $poItem->inventoryItem;
                $inventoryItem->update(['cost_per_unit' => $poItem->unit_cost]);

                if ($receivedLine['qty_received'] > 0) {
                    $service->recordTransaction(
                        $inventoryItem, 'purchase',
                        $receivedLine['qty_received'],
                        $purchaseOrder, $request->user()->id,
                        "PO receive: " . ($purchaseOrder->reference ?? "PO #{$purchaseOrder->id}")
                    );
                }
            }

            $purchaseOrder->update([
                'status'      => 'RECEIVED',
                'received_by' => $request->user()->id,
                'received_at' => now(),
            ]);

            return response()->json(['data' => $purchaseOrder->fresh()->load('items.inventoryItem')]);
        });
    }
}
