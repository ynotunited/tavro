<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private function authorizePayment(Request $request, Payment $payment): bool
    {
        $order = $payment->order;
        return $order
            && $order->organization_id === $request->user()->organization_id
            && $order->branch_id === $request->user()->branch_id;
    }

    /**
     * Get payments for a specific order.
     */
    public function index(Request $request, Order $order)
    {
        if ($order->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $order->payments()->with('processor')->get(),
            'total_amount' => $order->total_amount,
            'amount_paid' => $order->amount_paid,
            'is_fully_paid' => $order->isFullyPaid(),
        ]);
    }

    /**
     * Record a new payment attempt against an order.
     *
     * Flow:
     *  1. Client sends X-Idempotency-Key (UUID)
     *  2. Server checks ledger for duplicate → returns cached result if found
     *  3. Server creates Payment row (PENDING) + ledger INTENT entry
     *  4. For CASH/POS: immediately CAPTURED → COMPLETED
     *  5. For gateway methods: returns PENDING, awaits webhook confirmation
     */
    public function store(Request $request, Order $order)
    {
        if ($order->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');
        if (empty($idempotencyKey)) {
            return response()->json([
                'message' => 'X-Idempotency-Key header is required.',
            ], 422);
        }

        if (strlen($idempotencyKey) > 255) {
            return response()->json([
                'message' => 'Idempotency key must be 255 characters or fewer.',
            ], 422);
        }

        $validated = $request->validate([
            'amount'   => 'required|numeric|min:0.01|max:999999999',
            'method'   => 'required|in:CASH,TRANSFER,POS,CARD,PAYSTACK,FLUTTERWAVE',
            'reference' => 'nullable|string|max:255',
        ]);

        // ── Idempotency check: has this key already been processed? ─────────
        $existing = PaymentLedger::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['COMPLETED', 'PENDING', 'INTENT'])
            ->first();

        if ($existing) {
            $payment = Payment::find($existing->payment_id);
            Log::channel('payment')->info('Idempotent replay — returning cached result', [
                'idempotency_key' => $idempotencyKey,
                'payment_id'      => $payment?->id,
                'status'          => $existing->status,
            ]);
            return response()->json([
                'data' => $payment,
                'cached' => true,
            ], 200);
        }

        // ── Balance check (integer math in kobo to avoid float issues) ─────
        $remainingKobo = (int) round(($order->total_amount - $order->amount_paid) * 100);
        $amountKobo = (int) round($validated['amount'] * 100);

        if ($amountKobo > $remainingKobo) {
            return response()->json([
                'message' => 'Payment amount exceeds remaining balance of ' . number_format($remainingKobo / 100, 2),
            ], 422);
        }

        return DB::transaction(function () use ($validated, $order, $request, $idempotencyKey) {
            // ── 1. Create payment row ──────────────────────────────────────
            $status = in_array($validated['method'], ['CASH', 'POS']) ? 'COMPLETED' : 'PENDING';

            $payment = Payment::create([
                'order_id'         => $order->id,
                'amount'           => $validated['amount'],
                'method'           => $validated['method'],
                'status'           => $status,
                'reference'        => $validated['reference'] ?? null,
                'idempotency_key'  => $idempotencyKey,
                'processed_by'     => $request->user()->id,
            ]);

            // ── 2. Write INTENT to ledger (before any external call) ───────
            $payment->appendLedger(
                status: 'INTENT',
                idempotencyKey: $idempotencyKey,
                reference: $validated['reference'] ?? null,
                metadata: [
                    'amount' => $validated['amount'],
                    'method' => $validated['method'],
                    'order_total' => $order->total_amount,
                ],
                actorId: $request->user()->id,
            );

            // ── 3. For immediate methods, transition to COMPLETED ──────────
            if ($status === 'COMPLETED') {
                $payment->update(['status' => 'COMPLETED']);

                $payment->appendLedger(
                    status: 'COMPLETED',
                    idempotencyKey: $idempotencyKey,
                    metadata: ['reason' => 'immediate_method'],
                    actorId: $request->user()->id,
                );

                $this->markOrderPaidIfFullySettled($order);
            }

            Log::channel('payment')->info('Payment recorded', [
                'payment_id'    => $payment->id,
                'order_id'      => $order->id,
                'amount'        => $validated['amount'],
                'method'        => $validated['method'],
                'status'        => $status,
                'idempotency'   => $idempotencyKey,
                'processed_by'  => $request->user()->id,
                'org_id'        => $order->organization_id,
                'branch_id'     => $order->branch_id,
            ]);

            return response()->json(['data' => $payment], 201);
        });
    }

    /**
     * Manually confirm a pending payment (like a bank transfer).
     * Uses idempotency to prevent double-confirmation.
     */
    public function confirm(Request $request, Payment $payment)
    {
        if (!$this->authorizePayment($request, $payment)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key')
            ?: 'confirm-' . $payment->id . '-' . now()->timestamp;

        // ── Idempotency check ──────────────────────────────────────────────
        $alreadyCompleted = PaymentLedger::where('payment_id', $payment->id)
            ->where('status', 'COMPLETED')
            ->exists();

        if ($alreadyCompleted) {
            return response()->json([
                'data' => $payment->fresh(),
                'cached' => true,
            ]);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json(['message' => 'Only pending payments can be confirmed.'], 400);
        }

        return DB::transaction(function () use ($payment, $request, $idempotencyKey) {
            // Lock the payment row to prevent TOCTOU race
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($locked->status !== 'PENDING') {
                return response()->json(['message' => 'Payment was already processed.'], 409);
            }

            $locked->update(['status' => 'COMPLETED']);

            $locked->appendLedger(
                status: 'COMPLETED',
                idempotencyKey: $idempotencyKey,
                metadata: ['confirmed_by' => $request->user()->id],
                actorId: $request->user()->id,
            );

            $order = $locked->order;
            $this->markOrderPaidIfFullySettled($order);

            Log::channel('payment')->info('Payment confirmed', [
                'payment_id'   => $locked->id,
                'order_id'     => $order->id,
                'amount'       => $locked->amount,
                'method'       => $locked->method,
                'confirmed_by' => $request->user()->id,
                'org_id'       => $order->organization_id,
            ]);

            return response()->json(['data' => $locked]);
        });
    }

    /**
     * Mark order as PAID if total payments >= total amount.
     * Uses bccomp for exact currency comparison.
     */
    private function markOrderPaidIfFullySettled(Order $order): void
    {
        $order->refresh();
        $totalPaidKobo = (int) round($order->amount_paid * 100);
        $totalKobo = (int) round($order->total_amount * 100);

        if ($totalPaidKobo >= $totalKobo && $order->status !== 'VOIDED') {
            $order->update([
                'status'    => 'PAID',
                'closed_at' => $order->closed_at ?? now(),
            ]);
        }
    }
}
