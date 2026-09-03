<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\OpenBottle;
use Illuminate\Http\Request;

class BarInventoryController extends Controller
{
    // Fetch inventory items with open bottles
    public function getBarInventory(Request $request)
    {
        $branchId = $request->user()->branch_id;

        $items = InventoryItem::where('branch_id', $branchId)
            ->whereIn('category', ['Spirits', 'Mixers', 'Wine', 'Beer'])
            ->with(['openBottles' => function ($q) {
                $q->whereNull('empty_at')->orderBy('opened_at', 'desc');
            }])
            ->get();

        return response()->json(['data' => $items]);
    }

    // Open a new bottle
    public function openBottle(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'volume' => 'required|numeric|min:0',
        ]);

        $item = InventoryItem::where('id', $validated['inventory_item_id'])
            ->where('branch_id', $request->user()->branch_id)
            ->firstOrFail();

        // 1. Deduct 1 unit from stock (e.g. 1 bottle)
        $inventoryService = new \App\Services\InventoryService();
        $inventoryService->recordTransaction(
            $item,
            'adjustment',
            -1,
            null,
            $request->user()->id,
            "Opened new bottle"
        );

        // 2. Create open bottle record
        $openBottle = OpenBottle::create([
            'branch_id' => $request->user()->branch_id,
            'inventory_item_id' => $item->id,
            'current_volume' => $validated['volume'],
            'opened_at' => now(),
            'opened_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $openBottle], 201);
    }
}
