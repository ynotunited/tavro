<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function plans()
    {
        return $this->success(Plan::all());
    }

    public function current(Request $request)
    {
        $org = $request->user()->organization;

        if (!$org) {
            return $this->error('No organization found.', 404);
        }

        $subscription = Subscription::with('plan')
            ->where('organization_id', $org->id)
            ->first();

        $usage = [
            'branches' => $org->branches()->count(),
            'users' => $org->users()->count(),
        ];

        return $this->success([
            'subscription' => $subscription,
            'usage' => $usage,
        ]);
    }

    public function init(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        if (!config('services.paystack.secret_key')) {
            return $this->error('Payment provider not configured.', 500);
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $user = $request->user();
        $org = $user->organization;

        if (!$org) {
            return $this->error('No organization found.', 404);
        }

        $price = $this->priceForInterval($plan, $validated['billing_interval']);
        if ($price === null) {
            return $this->error('The selected billing interval is not available for this plan.', 422);
        }

        $planCode = $this->ensurePaystackPlan($plan, $validated['billing_interval'], $price);
        $customer = $this->ensurePaystackCustomer($user->email);

        return $this->success([
            'paystack_public_key' => config('services.paystack.public_key'),
            'plan_code' => $planCode,
            'email' => $user->email,
            'amount_kobo' => (int) round($price * 100),
            'billing_interval' => $validated['billing_interval'],
            'customer_code' => $customer,
        ]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'reference' => 'required|string|max:255',
        ]);

        $secretKey = config('services.paystack.secret_key');

        if (!$secretKey) {
            Log::error('Paystack secret key not configured — cannot verify payment');
            return $this->error('Payment provider not configured.', 500);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/transaction/verify/{$validated['reference']}");

        $body = $response->json();

        if (!$response->successful() || !data_get($body, 'status', false)) {
            Log::warning('Paystack transaction verification failed', [
                'reference' => $validated['reference'],
                'response' => $body,
            ]);
            return $this->error('Payment verification failed. Please try again.', 422);
        }

        $transaction = data_get($body, 'data', []);

        if (data_get($transaction, 'status') !== 'success') {
            return $this->error('Payment was not completed successfully.', 422);
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $price = $this->priceForInterval($plan, $validated['billing_interval']);

        if ($price === null) {
            return $this->error('The selected billing interval is not available for this plan.', 422);
        }

        $expectedAmountKobo = (int) round($price * 100);
        $receivedAmountKobo = (int) data_get($transaction, 'amount', 0);

        if ($receivedAmountKobo !== $expectedAmountKobo) {
            Log::warning('Paystack amount mismatch', [
                'reference' => $validated['reference'],
                'expected' => $expectedAmountKobo,
                'received' => $receivedAmountKobo,
            ]);
            return $this->error('Payment amount does not match the selected plan.', 422);
        }

        $paystackSub = data_get($transaction, 'subscription');
        $isRecurring = is_array($paystackSub) && data_get($paystackSub, 'subscription_code');

        $start = now();
        $end = $validated['billing_interval'] === 'yearly'
            ? $start->copy()->addYear()
            : $start->copy()->addMonth();

        $subscription = Subscription::updateOrCreate(
            ['organization_id' => $request->user()->organization->id],
            [
                'plan_id' => $validated['plan_id'],
                'billing_interval' => $validated['billing_interval'],
                'status' => 'active',
                'current_period_start' => $start,
                'current_period_end' => $end,
                'paystack_subscription_code' => $isRecurring ? data_get($paystackSub, 'subscription_code') : null,
                'paystack_customer_code' => $isRecurring
                    ? data_get($paystackSub, 'customer.customer_code')
                    : data_get($transaction, 'customer.customer_code'),
                'paystack_status' => $isRecurring ? 'active' : null,
                'autorenew' => $isRecurring,
                'next_payment_date' => $isRecurring ? $end : null,
            ]
        );

        return $this->success([
            'message' => 'Subscription activated successfully.',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    public function cancel(Request $request)
    {
        $org = $request->user()->organization;
        $subscription = Subscription::where('organization_id', $org->id)->first();

        if (!$subscription) {
            return $this->error('No active subscription found.', 404);
        }

        $secretKey = config('services.paystack.secret_key');

        if ($secretKey && $subscription->paystack_subscription_code) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.paystack.co/subscription/disable', [
                    'code' => $subscription->paystack_subscription_code,
                    'token' => $subscription->paystack_email_token,
                    'email_token' => $subscription->paystack_email_token,
                ]);
            } catch (\Exception $e) {
                Log::error('Paystack cancellation failed', [
                    'subscription_code' => $subscription->paystack_subscription_code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $subscription->update(['status' => 'canceled']);

        return $this->success('Subscription canceled successfully.');
    }

    private function priceForInterval(Plan $plan, string $interval): ?float
    {
        $price = $interval === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        return $price === null ? null : (float) $price;
    }

    private function ensurePaystackPlan(Plan $plan, string $interval, float $price): string
    {
        $existingCode = $interval === 'yearly'
            ? $plan->paystack_yearly_plan_code ?? null
            : $plan->paystack_plan_code;

        if ($existingCode) {
            return $existingCode;
        }

        $secretKey = config('services.paystack.secret_key');
        $headers = [
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ];

        $name = "{$plan->name} - " . ucfirst($interval);
        $created = Http::withHeaders($headers)->post('https://api.paystack.co/plan', [
            'name' => $name,
            'description' => "Tavro {$plan->name} plan ({$interval})",
            'amount' => (int) round($price * 100),
            'interval' => $interval === 'yearly' ? 'annually' : 'monthly',
            'currency' => 'NGN',
        ]);

        if (!$created->successful() || !data_get($created->json(), 'status', false)) {
            Log::error('Paystack plan creation failed', [
                'plan_id' => $plan->id,
                'interval' => $interval,
                'response' => $created->json(),
            ]);
            abort(422, 'Unable to set up billing plan. Please try again.');
        }

        $planCode = data_get($created->json('data'), 'plan_code', '');

        $plan->update($interval === 'yearly'
            ? ['paystack_yearly_plan_code' => $planCode]
            : ['paystack_plan_code' => $planCode]);

        return $planCode;
    }

    private function ensurePaystackCustomer(string $email): ?string
    {
        $secretKey = config('services.paystack.secret_key');
        $headers = ['Authorization' => 'Bearer ' . $secretKey, 'Content-Type' => 'application/json'];

        $lookup = Http::withHeaders($headers)
            ->get("https://api.paystack.co/customer/" . rawurlencode($email));

        if ($lookup->successful() && data_get($lookup->json(), 'status', false)) {
            return data_get($lookup->json('data'), 'customer_code');
        }

        $created = Http::withHeaders($headers)->post('https://api.paystack.co/customer', [
            'email' => $email,
        ]);

        if (!$created->successful() || !data_get($created->json(), 'status', false)) {
            Log::error('Paystack customer creation failed', [
                'email' => $email,
                'response' => $created->json(),
            ]);
            return null;
        }

        return data_get($created->json('data'), 'customer_code');
    }
}
