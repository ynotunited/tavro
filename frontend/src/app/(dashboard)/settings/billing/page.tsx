'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/axios';

interface Plan {
  id: number;
  name: string;
  price_monthly: string;
  features: {
    branches: number;
    users: number;
    terminals: number;
  };
}

interface SubscriptionData {
  subscription: {
    status: string;
    current_period_end: string;
    plan: Plan;
  } | null;
  usage: {
    branches: number;
    users: number;
  };
}

interface PaystackResponse {
  reference: string;
}

interface PaystackHandler {
  openIframe: () => void;
}

type PaystackSetup = (opts: {
  key: string;
  email: string;
  amount: number;
  currency: string;
  plan: string;
  customer?: string;
  onClose: () => void;
  callback: (response: PaystackResponse) => void;
}) => PaystackHandler;

interface PaystackWindow extends Window {
  PaystackPop?: { setup: PaystackSetup };
}

let paystackPromise: Promise<void> | null = null;

function loadPaystackScript(): Promise<void> {
  if (!paystackPromise) {
    paystackPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[src="https://js.paystack.co/v1/inline.js"]');
      if (existing || (window as unknown as PaystackWindow).PaystackPop) {
        resolve();
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://js.paystack.co/v1/inline.js';
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => {
        paystackPromise = null;
        reject(new Error('Failed to load Paystack'));
      };
      document.head.appendChild(script);
    });
  }
  return paystackPromise;
}

export default function BillingPage() {
  const [data, setData] = useState<SubscriptionData | null>(null);
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [subRes, planRes] = await Promise.all([
          api.get('/subscriptions/current'),
          api.get('/subscriptions/plans')
        ]);
        setData(subRes.data);
        setPlans(planRes.data.data);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  const handleSubscribe = async (planId: number) => {
    setProcessing(true);
    try {
      // 1. Ask the backend to ensure a Paystack plan + customer exist and hand
      //    back the public key / plan code / email for the recurring checkout.
      const initRes = await api.post('/subscriptions/init', { plan_id: planId });
      const init = initRes.data?.data;

      if (!init?.plan_code || !init?.paystack_public_key || !init?.email) {
        throw new Error('Checkout could not be initialized.');
      }

      // 2. Dynamically load Paystack's inline checkout script (only once).
      await loadPaystackScript();

      const paystack = (window as unknown as PaystackWindow).PaystackPop;
      if (!paystack) {
        throw new Error('Paystack could not be loaded.');
      }

      const handler = paystack.setup({
        key: init.paystack_public_key,
        email: init.email,
        amount: init.amount_kobo,
        currency: 'NGN',
        plan: init.plan_code,
        customer: init.customer_code ?? undefined,
        onClose: () => {
          setProcessing(false);
        },
        callback: async (response) => {
          try {
            // 3. Finalize on the backend — it verifies the transaction with
            //    Paystack, records the recurring subscription code, and sets
            //    autorenew + next payment date.
            const res = await api.post('/subscriptions/subscribe', {
              plan_id: planId,
              reference: response.reference,
            });
            alert(res.data?.message || 'Subscription activated successfully!');
            window.location.reload();
          } catch (err) {
            alert('Payment verified, but subscription finalization failed. Please contact support.');
            setProcessing(false);
          }
        },
      });
      handler.openIframe();
    } catch (err) {
      alert('Unable to start checkout. Please try again.');
      setProcessing(false);
    }
  };

  const handleCancel = async () => {
    if (!confirm('Are you sure you want to cancel your subscription?')) return;
    setProcessing(true);
    try {
      await api.post('/subscriptions/cancel');
      alert('Subscription canceled.');
      window.location.reload();
    } catch (err) {
      alert('Failed to cancel.');
    } finally {
      setProcessing(false);
    }
  };

  if (loading) return <div className="p-8 text-center text-gray-500">Loading Billing Data...</div>;

  const sub = data?.subscription;
  const usage = data?.usage;
  const isActive = sub?.status === 'active' || sub?.status === 'trialing';

  return (
    <div className="max-w-5xl mx-auto space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Subscription & Billing</h1>
        <p className="text-sm text-gray-500">Manage your plan and view usage limits.</p>
      </div>

      {/* Current Status */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex justify-between items-start">
          <div>
            <h2 className="text-lg font-bold text-charcoal">Current Plan: {sub?.plan?.name || 'Free Trial'}</h2>
            <p className="text-sm text-gray-500 mt-1">
              Status: 
              <span className={`ml-2 px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
              }`}>
                {sub?.status || 'trialing'}
              </span>
            </p>
            {isActive && sub?.current_period_end && (
              <p className="text-sm text-gray-500 mt-1">
                Renews on: {new Date(sub.current_period_end).toLocaleDateString()}
              </p>
            )}
          </div>
          {isActive ? (
            <button 
              onClick={handleCancel}
              disabled={processing}
              className="px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
            >
              Cancel Subscription
            </button>
          ) : null}
        </div>

        {/* Usage Limits */}
        <div className="mt-8 grid grid-cols-2 md:grid-cols-4 gap-6">
          <div className="space-y-1">
            <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Branches</p>
            <p className="text-2xl font-mono font-bold text-charcoal">
              {usage?.branches} <span className="text-lg text-gray-300">/ {sub?.plan?.features.branches === -1 ? '∞' : (sub?.plan?.features.branches || 1)}</span>
            </p>
          </div>
          <div className="space-y-1">
            <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Users</p>
            <p className="text-2xl font-mono font-bold text-charcoal">
              {usage?.users} <span className="text-lg text-gray-300">/ {sub?.plan?.features.users === -1 ? '∞' : (sub?.plan?.features.users || 3)}</span>
            </p>
          </div>
        </div>
      </div>

      {/* Pricing Grid */}
      <div>
        <h3 className="text-lg font-bold text-charcoal mb-4">Available Plans</h3>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {plans.map(plan => (
            <div key={plan.id} className={`bg-white rounded-xl shadow-sm border p-6 flex flex-col ${sub?.plan?.id === plan.id ? 'border-amber-500 ring-1 ring-amber-500' : 'border-gray-100'}`}>
              <h4 className="font-bold text-lg text-charcoal">{plan.name}</h4>
              <p className="text-2xl font-bold font-mono text-charcoal mt-2">
                ₦{Number(plan.price_monthly).toLocaleString()}
                <span className="text-xs text-gray-400 font-sans font-normal">/mo</span>
              </p>
              
              <div className="mt-6 flex-1 space-y-3 text-sm text-gray-600">
                <p>✓ {plan.features.branches === -1 ? 'Unlimited' : plan.features.branches} Branch{plan.features.branches !== 1 ? 'es' : ''}</p>
                <p>✓ {plan.features.users === -1 ? 'Unlimited' : plan.features.users} User{plan.features.users !== 1 ? 's' : ''}</p>
                <p>✓ {plan.features.terminals === -1 ? 'Unlimited' : plan.features.terminals} POS Terminal{plan.features.terminals !== 1 ? 's' : ''}</p>
              </div>

              <button 
                onClick={() => handleSubscribe(plan.id)}
                disabled={processing || sub?.plan?.id === plan.id}
                className={`mt-6 w-full py-2 rounded-lg text-sm font-semibold transition-colors ${
                  sub?.plan?.id === plan.id 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : 'bg-charcoal text-white hover:bg-gray-800'
                }`}
              >
                {sub?.plan?.id === plan.id ? 'Current Plan' : 'Select Plan'}
              </button>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
