<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handle payment-provider webhooks.
 *
 * Security & idempotency live in App\Http\Middleware\VerifyWebhook:
 *   - URL-token protection, IP allowlist, provider signature — enforced there.
 * Controller responsibility is the atomic idempotency claim + business logic.
 *
 * Idempotency state machine (status column):
 *   RECEIVED  → first atomic insert claims the event
 *   PROCESSED → handled successfully (terminal)
 *   DUPLICATE → acknowledged replay of a PROCESSED event
 *   FAILED    → processing errored; the provider's retry re-claims it
 *
 * The composite unique key (provider, event_id) makes the first insert atomic,
 * so two concurrent deliveries of the same event can never both process.
 */
class WebhookController extends Controller
{
    private const CLAIM_STALE_MINUTES = 15;

    /**
     * Handle Paystack webhook.
     *
     * Flow:
     *  1. Atomically claim the event (composite unique key + status RECEIVED)
     *  2. Return 'duplicate' for replays / in-flight duplicates
     *  3. Append LEDGER entry (never overwrites existing rows)
     *  4. Update payment status + order if fully paid
     *  5. Mark event PROCESSED on success / FAILED (→ provider retry) on error
     */
    public function paystack(Request $request): JsonResponse
    {
        $event     = $request->input('event');
        $data      = $request->input('data');
        $eventId   = (string) $request->input('id');
        $reference = $data['reference'] ?? null;

        $eventRow = $this->claimEvent(
            provider: 'paystack',
            eventId:  $eventId,
            eventType: $event,
            reference: $reference,
            payload:  $request->input(),
            rawBody:  $request->getContent(),
            ip:       $request->ip(),
        );

        if ($eventRow === null) {
            Log::channel('payment')->info('Paystack webhook duplicate — skipping', [
                'event_id' => $eventId,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['status' => 'duplicate']);
        }

        try {
            if ($event === 'charge.success' && $reference) {
                $this->processSuccessfulPayment(
                    provider: 'paystack',
                    reference: $reference,
                    providerEventId: $eventId,
                    metadata: [
                        'gateway_amount'  => $data['amount'] ?? null,
                        'gateway_currency' => $data['currency'] ?? null,
                        'gateway_channel' => $data['channel'] ?? null,
                        'gateway_ip'      => $data['ip_address'] ?? null,
                    ],
                );

                // Recurring renewal charge (not tied to an in-app PENDING payment):
                // advance the subscription period so access continues uninterrupted.
                $this->handleRecurringRenewal($data);
            }

            if ($event === 'charge.failed' && $reference) {
                $this->processFailedPayment(
                    provider: 'paystack',
                    reference: $reference,
                    providerEventId: $eventId,
                    metadata: [
                        'failure_reason' => $data['gateway_response'] ?? 'Unknown',
                    ],
                );
            }

            // Subscription lifecycle: auto-renewal + status transitions.
            if ($event === 'invoice.payment_failed') {
                $this->handleInvoicePaymentFailed($data);
            }

            if ($event === 'subscription.disable' || $event === 'subscription.disable_from_restart') {
                $this->handleSubscriptionDisabled($data);
            }

            $eventRow->update(['status' => 'PROCESSED', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $eventRow->update(['status' => 'FAILED', 'error' => $e->getMessage()]);
            Log::channel('payment')->error('Paystack webhook processing failed', [
                'event_id' => $eventId,
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        Log::channel('payment')->info('Paystack webhook processed', [
            'event'     => $event,
            'event_id'  => $eventId,
            'reference' => $reference,
            'ip'        => $request->ip(),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave webhook.
     */
    public function flutterwave(Request $request): JsonResponse
    {
        $event     = $request->input('event');
        $data      = $request->input('data');
        $eventId   = (string) ($data['id'] ?? $request->input('id'));
        $reference = $data['tx_ref'] ?? null;

        $eventRow = $this->claimEvent(
            provider: 'flutterwave',
            eventId:  $eventId,
            eventType: $event,
            reference: $reference,
            payload:  $request->input(),
            rawBody:  $request->getContent(),
            ip:       $request->ip(),
        );

        if ($eventRow === null) {
            Log::channel('payment')->info('Flutterwave webhook duplicate — skipping', [
                'event_id' => $eventId,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['status' => 'duplicate']);
        }

        try {
            if ($event === 'charge.completed' && ($data['status'] ?? '') === 'successful' && $reference) {
                $this->processSuccessfulPayment(
                    provider: 'flutterwave',
                    reference: $reference,
                    providerEventId: $eventId,
                    metadata: [
                        'gateway_amount'   => $data['amount'] ?? null,
                        'gateway_currency' => $data['currency'] ?? null,
                        'gateway_channel'  => $data['channel'] ?? null,
                        'gateway_ip'       => $data['ip'] ?? null,
                    ],
                );
            }

            if ($event === 'charge.failed' || (($data['status'] ?? '') === 'failed')) {
                $this->processFailedPayment(
                    provider: 'flutterwave',
                    reference: $reference,
                    providerEventId: $eventId,
                    metadata: [
                        'failure_reason' => $data['gateway_response'] ?? 'Unknown',
                    ],
                );
            }

            $eventRow->update(['status' => 'PROCESSED', 'processed_at' => now()]);
        } catch (\Throwable $e) {
            $eventRow->update(['status' => 'FAILED', 'error' => $e->getMessage()]);
            Log::channel('payment')->error('Flutterwave webhook processing failed', [
                'event_id' => $eventId,
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }

        Log::channel('payment')->info('Flutterwave webhook processed', [
            'event'     => $event,
            'event_id'  => $eventId,
            'reference' => $reference,
            'ip'        => $request->ip(),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Atomically claim a webhook event for processing.
     *
     * The FIRST insert for a (provider, event_id) wins via the composite unique
     * constraint — a concurrent duplicate triggers UniqueConstraintViolation and
     * is reconciled instead of double-processed. Returns null for any replay.
     */
    private function claimEvent(
        string $provider,
        string $eventId,
        ?string $eventType,
        ?string $reference,
        array $payload,
        string $rawBody,
        string $ip,
    ): ?WebhookEvent {
        if ($eventId === '') {
            // No provider event id → cannot dedupe; record a unique synthetic row.
            $eventId = 'anon-' . hash('sha256', $rawBody);
        }

        try {
            return WebhookEvent::create([
                'provider'     => $provider,
                'event_id'     => $eventId,
                'event_type'   => $eventType,
                'reference'    => $reference,
                'payload'      => $payload,
                'payload_hash' => hash('sha256', $rawBody),
                'received_ip'  => $ip,
                'attempts'     => 1,
                'status'       => 'RECEIVED',
                'created_at'   => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Replay / provider retry / concurrent duplicate — reconcile below.
        }

        /** @var WebhookEvent|null */
        $existing = WebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();

        if ($existing === null) {
            return null; // Row vanished mid-flight — treat as duplicate, do not double-process.
        }

        return $this->reconcileClaim($existing);
    }

    /**
     * Decide what a duplicate delivery means given the existing row's state.
     */
    private function reconcileClaim(WebhookEvent $existing): ?WebhookEvent
    {
        switch ($existing->status) {
            case 'FAILED':
                // Provider retry after our 500 — reclaim and reprocess.
                $existing->update([
                    'status'    => 'RECEIVED',
                    'attempts'  => $existing->attempts + 1,
                    'error'     => null,
                ]);
                return $existing;

            case 'RECEIVED':
                // In-flight claim. Only reclaim a stale one (processing crashed).
                $stale = $existing->created_at?->lt(now()->subMinutes(self::CLAIM_STALE_MINUTES)) ?? false;
                if (!$stale) {
                    $existing->increment('attempts');
                    return null; // Concurrent delivery — the in-flight request finishes it.
                }
                $existing->update([
                    'status'   => 'RECEIVED',
                    'attempts' => $existing->attempts + 1,
                    'error'    => 'Reclaimed after stale claim.',
                ]);
                return $existing;

            case 'PROCESSED':
                // Replay of an already-handled event — acknowledge, don't re-run.
                $existing->update([
                    'status'   => 'DUPLICATE',
                    'attempts' => $existing->attempts + 1,
                    'error'    => 'Replay of an already-processed event.',
                ]);
                return null;

            case 'DUPLICATE':
            default:
                $existing->increment('attempts');
                return null;
        }
    }

    /**
     * A successful recurring (renewal) charge — advance the subscription period.
     * Only acts when the transaction carries a Paystack subscription object and a
     * matching local subscription exists; otherwise it's an ordinary charge and
     * is a no-op here.
     */
    private function handleRecurringRenewal(array $data): void
    {
        $sub = data_get($data, 'subscription', null);
        if (!is_array($sub)) {
            return;
        }

        $subscriptionCode = data_get($sub, 'subscription_code');
        if (!$subscriptionCode) {
            return;
        }

        $subscription = Subscription::where('paystack_subscription_code', $subscriptionCode)->first();
        if (!$subscription) {
            $this->logSubscriptionEvent('Renewal received but no local subscription matched', $subscriptionCode);
            return;
        }

        $subscription->advanceToNextPeriod();

        Log::channel('payment')->info('Paystack recurring renewal applied', [
            'organization_id'        => $subscription->organization_id,
            'subscription_code'      => $subscriptionCode,
            'current_period_end'     => $subscription->current_period_end?->toIso8601String(),
            'next_payment_date'      => $subscription->next_payment_date?->toIso8601String(),
        ]);
    }

    /**
     * A subscription renewal/charge failed — transition to past_due.
     */
    private function handleInvoicePaymentFailed(array $data): void
    {
        $sub = data_get($data, 'subscription', []);
        $subscriptionCode = data_get($sub, 'subscription_code') ?? data_get($data, 'subscription_code');

        $subscription = $subscriptionCode
            ? Subscription::where('paystack_subscription_code', $subscriptionCode)->first()
            : null;

        if (!$subscription) {
            $this->logSubscriptionEvent('Invoice payment failed but no local subscription matched', $subscriptionCode);
            return;
        }

        $subscription->markPastDue();

        Log::channel('payment')->warning('Paystack invoice payment failed — subscription past_due', [
            'organization_id'   => $subscription->organization_id,
            'subscription_code' => $subscriptionCode,
        ]);
    }

    /**
     * Paystack disabled the subscription (user cancelled / plan turned off).
     */
    private function handleSubscriptionDisabled(array $data): void
    {
        $subscriptionCode = data_get($data, 'subscription_code') ?? data_get($data, 'code');

        $subscription = $subscriptionCode
            ? Subscription::where('paystack_subscription_code', $subscriptionCode)->first()
            : null;

        if (!$subscription) {
            $this->logSubscriptionEvent('Subscription disabled but no local subscription matched', $subscriptionCode);
            return;
        }

        $subscription->markCanceled();

        Log::channel('payment')->warning('Paystack subscription disabled — subscription canceled', [
            'organization_id'   => $subscription->organization_id,
            'subscription_code' => $subscriptionCode,
        ]);
    }

    /**
     * Centralised logging for subscription webhook events.
     */
    private function logSubscriptionEvent(string $message, ?string $subscriptionCode): void
    {
        Log::channel('payment')->info("Paystack subscription webhook — {$message}", [
            'subscription_code' => $subscriptionCode,
        ]);
    }

    /**
     * Shared logic: transition a PENDING payment to COMPLETED via webhook.
     * Appends ledger entry. Never overwrites existing completed state.
     */
    private function processSuccessfulPayment(
        string $provider,
        string $reference,
        ?string $providerEventId,
        array $metadata,
    ): void {
        $payment = Payment::where('reference', $reference)
            ->where('status', 'PENDING')
            ->first();

        if (!$payment) {
            Log::channel('payment')->warning("{$provider} webhook — payment not found or already processed", [
                'reference' => $reference,
            ]);
            return;
        }

        DB::transaction(function () use ($payment, $providerEventId, $metadata) {
            // Lock to prevent concurrent webhook race
            $locked = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($locked->status !== 'PENDING') {
                return; // Already processed by another webhook or manual confirm
            }

            $locked->update(['status' => 'COMPLETED']);

            // Append LEDGER entry — never overwrite
            $locked->appendLedger(
                status: 'COMPLETED',
                providerEventId: $providerEventId,
                metadata: $metadata,
            );

            // Mark order as paid if fully settled
            $order = $locked->order;
            $totalPaidKobo = (int) round($order->amount_paid * 100);
            $totalKobo = (int) round($order->total_amount * 100);

            if ($totalPaidKobo >= $totalKobo && $order->status !== 'VOIDED') {
                $order->update([
                    'status'    => 'PAID',
                    'closed_at' => $order->closed_at ?? now(),
                ]);
            }
        });

        Log::channel('payment')->info("{$provider} webhook — payment completed", [
            'payment_id' => $payment->id,
            'reference'  => $reference,
            'amount'     => $payment->amount,
            'order_id'   => $payment->order_id,
            'event_id'   => $providerEventId,
        ]);
    }

    /**
     * Shared logic: record a failed payment attempt in the ledger.
     */
    private function processFailedPayment(
        string $provider,
        ?string $reference,
        ?string $providerEventId,
        array $metadata,
    ): void {
        if (!$reference) return;

        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            Log::channel('payment')->warning("{$provider} webhook — failed payment not found", [
                'reference' => $reference,
            ]);
            return;
        }

        $payment->update(['status' => 'FAILED']);

        $payment->appendLedger(
            status: 'FAILED',
            providerEventId: $providerEventId,
            metadata: $metadata,
        );

        Log::channel('payment')->info("{$provider} webhook — payment failed", [
            'payment_id' => $payment->id,
            'reference'  => $reference,
            'event_id'   => $providerEventId,
            'reason'     => $metadata['failure_reason'] ?? 'Unknown',
        ]);
    }
}
