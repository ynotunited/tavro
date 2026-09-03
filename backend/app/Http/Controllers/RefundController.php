<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    /**
     * Process a refund for a payment.
     */
    public function store(Request $request, Payment $payment)
    {
        // Verify payment belongs to user's organization and branch
        $order = $payment->order;
        if (!$order
            || $order->organization_id !== $request->user()->organization_id
            || $order->branch_id !== $request->user()->branch_id
        ) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only managers and owners can process refunds
        if (!$request->user()->hasRole(['owner', 'general_manager', 'branch_manager'])) {
            return response()->json(['message' => 'Unauthorized to process refunds.'], 403);
        }

        if ($payment->status !== 'COMPLETED') {
            return response()->json(['message' => 'Only completed payments can be refunded.'], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $payment, $request) {
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'reason' => $validated['reason'],
                'approved_by' => $request->user()->id,
            ]);

            $payment->update(['status' => 'REFUNDED']);

            $order = $payment->order;
            if (!$order->isFullyPaid()) {
                $order->update([
                    'status' => 'PAYMENT_PENDING',
                    'closed_at' => null,
                ]);
            }

            return response()->json(['data' => $refund], 201);
        });
    }
}
