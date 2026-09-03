<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\WastageEntry;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    // GET /inventory
    public function index(Request $request)
    {
        $items = InventoryItem::where('branch_id', $request->user()->branch_id)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    // POST /inventory
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'required|string|in:piece,bottle,kg,g,ml,litre,box,case,pack,dozen',
            'cost_per_unit' => 'required|numeric|min:0|max:999999999',
            'min_level' => 'nullable|numeric|min:0|max:999999',
            'bottle_size_ml' => 'nullable|numeric|min:0|max:99999',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'track_inventory' => 'boolean',
        ]);

        $item = InventoryItem::create([
            ...$validated,
            'branch_id' => $request->user()->branch_id,
        ]);

        return response()->json(['data' => $item], 201);
    }

    // PUT /inventory/{item}
    public function update(Request $request, InventoryItem $item)
    {
        if ($item->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100|unique:inventory_items,sku',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'sometimes|string|in:piece,bottle,kg,g,ml,litre,box,case,pack,dozen',
            'cost_per_unit' => 'sometimes|numeric|min:0|max:999999999',
            'min_level' => 'nullable|numeric|min:0|max:999999',
            'bottle_size_ml' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'track_inventory' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json(['data' => $item]);
    }

    // POST /inventory/receive
    public function receive(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.001|max:999999',
            'notes' => 'nullable|string|max:500',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($item->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $service = new InventoryService;
        $transaction = $service->recordTransaction(
            $item, 'purchase', $validated['quantity'],
            null, $request->user()->id, $validated['notes'] ?? 'Manual stock receive'
        );

        return response()->json(['data' => $transaction], 201);
    }

    // POST /inventory/adjust
    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity_change' => 'required|numeric|min:-999999|max:999999',
            'notes' => 'required|string|max:500',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($item->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $service = new InventoryService;
        $transaction = $service->recordTransaction(
            $item, 'adjustment', $validated['quantity_change'],
            null, $request->user()->id, $validated['notes']
        );

        return response()->json(['data' => $transaction], 201);
    }

    // POST /inventory/wastage
    public function wastage(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.001|max:999999',
            'type' => 'required|in:spoilage,breakage,over-pour,kitchen-error,wrong-order,expired,other',
            'notes' => 'nullable|string|max:500',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($item->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $totalCost = $item->cost_per_unit * $validated['quantity'];
        $HIGH_VALUE_THRESHOLD = 5000;

        $entry = WastageEntry::create([
            'branch_id' => $request->user()->branch_id,
            'inventory_item_id' => $item->id,
            'quantity' => $validated['quantity'],
            'unit_cost' => $item->cost_per_unit,
            'total_cost' => $totalCost,
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
            'requires_approval' => $totalCost >= $HIGH_VALUE_THRESHOLD,
            'recorded_by' => $request->user()->id,
        ]);

        if (! $entry->requires_approval) {
            $service = new InventoryService;
            $service->recordTransaction(
                $item, 'wastage', -$validated['quantity'],
                null, $request->user()->id,
                "Wastage: {$validated['type']} — ".($validated['notes'] ?? '')
            );
        }

        return response()->json(['data' => $entry], 201);
    }
}
