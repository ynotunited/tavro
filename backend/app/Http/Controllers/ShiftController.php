<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    // Get current active shift for user
    public function active(Request $request)
    {
        $shift = Shift::where('user_id', $request->user()->id)
            ->whereIn('status', ['OPEN', 'CLOSING'])
            ->latest()
            ->first();

        return response()->json(['data' => $shift]);
    }

    // List recent shifts for history
    public function index(Request $request)
    {
        $shifts = Shift::where('branch_id', $request->user()->branch_id)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('opened_at')
            ->limit(20)
            ->get();
            
        return response()->json(['data' => $shifts]);
    }

    // Open a new shift
    public function store(Request $request)
    {
        $active = Shift::where('user_id', $request->user()->id)
            ->whereIn('status', ['OPEN', 'CLOSING'])
            ->first();

        if ($active) {
            return response()->json(['message' => 'You already have an active shift.', 'shift' => $active], 400);
        }

        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $shift = Shift::create([
            'branch_id' => $request->user()->branch_id,
            'user_id' => $request->user()->id,
            'status' => 'OPEN',
            'opening_cash' => $validated['opening_cash'],
            'opened_at' => now(),
        ]);

        return response()->json(['data' => $shift], 201);
    }

    // Prepare shift for closing (calculate expected cash)
    public function prepareClose(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($shift->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($shift->status !== 'OPEN') {
            return response()->json(['message' => 'Shift is not open.'], 400);
        }

        $expected = $shift->calculateExpectedCash();

        return response()->json([
            'expected_cash' => $expected,
            'total_sales' => $shift->totalCashSales(),
            'total_refunds' => $shift->totalCashRefunds(),
        ]);
    }

    // Close shift and record variance
    public function close(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($shift->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($shift->status === 'CLOSED') {
            return response()->json(['message' => 'Shift is already closed.'], 400);
        }

        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'variance_reason' => 'nullable|string',
        ]);

        $expected = $shift->calculateExpectedCash();
        $variance = $validated['actual_cash'] - $expected;
        
        $requiresApproval = abs($variance) > 500;

        $shift->update([
            'status' => $requiresApproval ? 'CLOSING' : 'CLOSED',
            'closing_cash_actual' => $validated['actual_cash'],
            'expected_cash' => $expected,
            'cash_variance' => $variance,
            'variance_reason' => $validated['variance_reason'] ?? null,
            'closed_at' => now(),
        ]);

        return response()->json(['data' => $shift]);
    }

    // Manager approve variance
    public function approveVariance(Request $request, Shift $shift)
    {
        if ($shift->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$request->user()->hasRole(['owner', 'general_manager', 'branch_manager'])) {
            return response()->json(['message' => 'Only managers can approve variance.'], 403);
        }

        if ($shift->status !== 'CLOSING') {
            return response()->json(['message' => 'Shift is not pending variance approval.'], 400);
        }

        $shift->update([
            'status' => 'CLOSED',
            'variance_approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $shift]);
    }
}
