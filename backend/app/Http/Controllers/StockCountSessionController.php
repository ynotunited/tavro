<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockCountSession;
use App\Models\StockCountEntry;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockCountSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = StockCountSession::where('branch_id', $request->user()->branch_id)
            ->with(['startedBy', 'approvedBy'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    // Start a new count session
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'            => 'required|in:full,category,bar,individual',
            'category_filter' => 'nullable|string|max:100',
        ]);

        $session = StockCountSession::create([
            'branch_id'       => $request->user()->branch_id,
            'type'            => $validated['type'],
            'category_filter' => $validated['category_filter'] ?? null,
            'status'          => 'DRAFT',
            'started_by'      => $request->user()->id,
        ]);

        $query = InventoryItem::where('branch_id', $request->user()->branch_id)
            ->where('track_inventory', true);

        if ($validated['type'] === 'category' && !empty($validated['category_filter'])) {
            $query->where('category', $validated['category_filter']);
        } elseif ($validated['type'] === 'bar') {
            $query->whereIn('category', ['Spirits', 'Mixers', 'Wine', 'Beer']);
        }

        $items = $query->get();
        foreach ($items as $item) {
            StockCountEntry::create([
                'stock_count_session_id' => $session->id,
                'inventory_item_id'      => $item->id,
                'expected_qty'           => $item->current_stock,
                'actual_qty'             => $item->current_stock,
                'variance_value'         => 0,
            ]);
        }

        return response()->json(['data' => $session->load('entries.inventoryItem')], 201);
    }

    // Update entry actual quantities
    public function updateEntries(Request $request, StockCountSession $session)
    {
        if ($session->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($session->status !== 'DRAFT') {
            return response()->json(['message' => 'Can only update a DRAFT count session.'], 400);
        }

        $validated = $request->validate([
            'entries'              => 'required|array',
            'entries.*.id'         => 'required|exists:stock_count_entries,id',
            'entries.*.actual_qty' => 'required|numeric|min:0',
        ]);

        foreach ($validated['entries'] as $entryData) {
            StockCountEntry::where('id', $entryData['id'])
                ->where('stock_count_session_id', $session->id)
                ->update(['actual_qty' => $entryData['actual_qty']]);
        }

        return response()->json(['data' => $session->fresh()->load('entries.inventoryItem')]);
    }

    // Submit count for approval
    public function submit(Request $request, StockCountSession $session)
    {
        if ($session->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($session->status !== 'DRAFT') {
            return response()->json(['message' => 'Session is not in DRAFT status.'], 400);
        }

        $session->update([
            'status'       => 'SUBMITTED',
            'submitted_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $session]);
    }

    // Approve count and apply variances to actual stock
    public function approve(Request $request, StockCountSession $session)
    {
        if ($session->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$request->user()->hasRole(['owner', 'general_manager', 'branch_manager'])) {
            return response()->json(['message' => 'Only managers can approve stock counts.'], 403);
        }

        if ($session->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Session must be SUBMITTED before approval.'], 400);
        }

        return DB::transaction(function () use ($session, $request) {
            $service = new InventoryService();

            foreach ($session->entries as $entry) {
                $item = $entry->inventoryItem;
                $varianceQty = $entry->actual_qty - $entry->expected_qty;
                $varianceValue = round($varianceQty * $item->cost_per_unit, 2);

                $entry->update(['variance_value' => $varianceValue]);

                if (abs($varianceQty) > 0.0001) {
                    $service->recordTransaction(
                        $item, 'adjustment', $varianceQty,
                        $session, $request->user()->id,
                        "Stock count adjustment (Session #{$session->id})"
                    );
                }
            }

            $session->update([
                'status'      => 'APPROVED',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return response()->json(['data' => $session->fresh()->load('entries.inventoryItem')]);
        });
    }
}
