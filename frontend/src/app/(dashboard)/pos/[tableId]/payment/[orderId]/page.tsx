'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Payment {
  id: number;
  amount: string;
  method: string;
  status: string;
  reference: string | null;
  processed_by: number | null;
  created_at: string;
}

interface PaymentsResponse {
  data: Payment[];
  total_amount: string;
  amount_paid: string;
  is_fully_paid: boolean;
}

type PaymentMethod = 'CASH' | 'TRANSFER' | 'POS' | 'CARD' | 'PAYSTACK' | 'FLUTTERWAVE';

interface SplitLine {
  method: PaymentMethod;
  amount: string;
  reference: string;
}

const METHOD_ICONS: Record<PaymentMethod, string> = {
  CASH: '💵',
  TRANSFER: '🏦',
  POS: '💳',
  CARD: '💳',
  PAYSTACK: '🔵',
  FLUTTERWAVE: '🌊',
};

const METHOD_LABELS: Record<PaymentMethod, string> = {
  CASH: 'Cash',
  TRANSFER: 'Bank Transfer',
  POS: 'POS Terminal',
  CARD: 'Card',
  PAYSTACK: 'Paystack',
  FLUTTERWAVE: 'Flutterwave',
};

const METHODS: PaymentMethod[] = ['CASH', 'TRANSFER', 'POS', 'CARD', 'PAYSTACK', 'FLUTTERWAVE'];

export default function PaymentPage() {
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const orderId = params.orderId as string;
  const tableId = params.tableId as string;

  const [splitLines, setSplitLines] = useState<SplitLine[]>([
    { method: 'CASH', amount: '', reference: '' },
  ]);

  // Fetch existing payments
  const { data, isLoading } = useQuery<PaymentsResponse>({
    queryKey: ['payments', orderId],
    queryFn: async () => (await api.get(`/orders/${orderId}/payments`)).data,
    refetchInterval: 5000,
  });

  const totalAmount = Number(data?.total_amount ?? 0);
  const amountPaid = Number(data?.amount_paid ?? 0);
  const balance = totalAmount - amountPaid;
  const isFullyPaid = data?.is_fully_paid ?? false;

  // Pre-fill the split line with remaining balance
  useEffect(() => {
    if (balance > 0 && splitLines.length === 1 && splitLines[0].amount === '') {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setSplitLines([{ method: 'CASH', amount: balance.toFixed(2), reference: '' }]);
    }
  }, [balance, splitLines]);

  const recordPaymentMutation = useMutation({
    mutationFn: async (line: SplitLine) =>
      api.post(`/orders/${orderId}/payments`, {
        amount: parseFloat(line.amount),
        method: line.method,
        reference: line.reference || undefined,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payments', orderId] });
    },
  });

  const confirmPaymentMutation = useMutation({
    mutationFn: async (paymentId: number) =>
      api.post(`/payments/${paymentId}/confirm`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payments', orderId] });
    },
  });

  const addSplitLine = () => {
    setSplitLines(prev => [...prev, { method: 'CASH', amount: '', reference: '' }]);
  };

  const updateLine = (index: number, field: keyof SplitLine, value: string) => {
    setSplitLines(prev => prev.map((line, i) => i === index ? { ...line, [field]: value } : line));
  };

  const removeLine = (index: number) => {
    setSplitLines(prev => prev.filter((_, i) => i !== index));
  };

  const handleRecordAll = async () => {
    for (const line of splitLines) {
      if (parseFloat(line.amount) > 0) {
        await recordPaymentMutation.mutateAsync(line);
      }
    }
    setSplitLines([{ method: 'CASH', amount: '', reference: '' }]);
  };

  const splitTotal = splitLines.reduce((sum, l) => sum + (parseFloat(l.amount) || 0), 0);
  const isOverpaid = amountPaid + splitTotal > totalAmount;

  if (isLoading) {
    return <div className="min-h-screen bg-[#0F172A] flex items-center justify-center text-white">Loading...</div>;
  }

  return (
    <div className="min-h-screen bg-[#0F172A] text-white flex flex-col max-w-2xl mx-auto">

      {/* Header */}
      <div className="flex items-center gap-4 px-6 py-4 border-b border-white/10">
        <button
          onClick={() => router.push(`/pos/${tableId}`)}
          className="text-white/40 hover:text-white transition-colors text-xl"
        >
          ←
        </button>
        <div>
          <h1 className="text-lg font-bold tracking-tight text-white">Payment</h1>
          <p className="text-xs text-white/40">Order #{orderId}</p>
        </div>
        {isFullyPaid && (
          <span className="ml-auto bg-emerald-500/20 text-emerald-400 text-sm font-bold px-3 py-1 rounded">
            ✓ PAID
          </span>
        )}
      </div>

      <div className="flex-1 overflow-y-auto px-6 py-6 space-y-6">

        {/* ── Total Display ── */}
        <div className="text-center py-8">
          <p className="text-sm text-white/40 uppercase tracking-widest mb-2">Total Amount Due</p>
          <p className="text-6xl font-bold tracking-tighter font-mono text-amber-400">
            ₦{totalAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 })}
          </p>
          {amountPaid > 0 && (
            <div className="mt-4 flex justify-center gap-6 text-sm">
              <div>
                <p className="text-white/30">Paid</p>
                <p className="font-mono text-emerald-400 font-bold">₦{amountPaid.toLocaleString()}</p>
              </div>
              <div>
                <p className="text-white/30">Balance</p>
                <p className={`font-mono font-bold ${balance > 0 ? 'text-red-400' : 'text-emerald-400'}`}>
                  ₦{Math.abs(balance).toLocaleString()}
                  {balance <= 0 && ' Change'}
                </p>
              </div>
            </div>
          )}
        </div>

        {/* ── Existing Payments Ledger ── */}
        {(data?.data ?? []).length > 0 && (
          <div className="space-y-2">
            <p className="text-xs text-white/40 uppercase tracking-widest font-semibold">Payment History</p>
            <div className="bg-white/5 rounded-xl divide-y divide-white/5 overflow-hidden border border-white/10">
              {(data?.data ?? []).map(payment => (
                <div key={payment.id} className="px-4 py-3 flex justify-between items-center gap-4">
                  <div className="flex items-center gap-3">
                    <span className="text-xl">{METHOD_ICONS[payment.method as PaymentMethod]}</span>
                    <div>
                      <p className="text-sm font-medium">{METHOD_LABELS[payment.method as PaymentMethod]}</p>
                      {payment.reference && (
                        <p className="text-xs text-white/30 font-mono">{payment.reference}</p>
                      )}
                    </div>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="font-mono font-bold text-sm">₦{Number(payment.amount).toLocaleString()}</p>
                    <div className="flex items-center justify-end gap-2 mt-0.5">
                      <span className={`text-xs font-semibold ${
                        payment.status === 'COMPLETED' ? 'text-emerald-400' :
                        payment.status === 'PENDING' ? 'text-amber-400' :
                        'text-red-400'
                      }`}>
                        {payment.status}
                      </span>
                      {payment.status === 'PENDING' && (
                        <button
                          onClick={() => confirmPaymentMutation.mutate(payment.id)}
                          disabled={confirmPaymentMutation.isPending}
                          className="text-xs text-white/60 bg-white/10 hover:bg-white/20 px-2 py-0.5 rounded transition-colors"
                        >
                          Confirm
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── Split Payment Builder ── */}
        {!isFullyPaid && (
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <p className="text-xs text-white/40 uppercase tracking-widest font-semibold">Record Payment</p>
              <button
                onClick={addSplitLine}
                className="text-xs text-amber-400 hover:text-amber-300 font-medium transition-colors"
              >
                + Add Split
              </button>
            </div>

            {splitLines.map((line, index) => (
              <div key={index} className="bg-white/5 border border-white/10 rounded-xl p-4 space-y-3">
                {/* Method Grid */}
                <div className="grid grid-cols-3 gap-2">
                  {METHODS.map(method => (
                    <button
                      key={method}
                      onClick={() => updateLine(index, 'method', method)}
                      className={`flex flex-col items-center py-2.5 px-2 rounded-lg text-xs font-medium transition-colors border ${
                        line.method === method
                          ? 'bg-amber-400 text-[#0F172A] border-amber-400 font-bold'
                          : 'bg-white/5 text-white/60 border-white/10 hover:border-white/30'
                      }`}
                    >
                      <span className="text-lg mb-0.5">{METHOD_ICONS[method]}</span>
                      {METHOD_LABELS[method]}
                    </button>
                  ))}
                </div>

                {/* Amount & Reference Row */}
                <div className="flex gap-3">
                  <div className="flex-1">
                    <input
                      type="number"
                      value={line.amount}
                      onChange={e => updateLine(index, 'amount', e.target.value)}
                      placeholder="₦ Amount"
                      className="w-full bg-white/5 border border-white/10 text-white font-mono text-xl font-bold text-center py-3 rounded-lg outline-none focus:border-amber-400/50 placeholder-white/20"
                    />
                  </div>
                  {['TRANSFER', 'PAYSTACK', 'FLUTTERWAVE', 'POS'].includes(line.method) && (
                    <div className="flex-1">
                      <input
                        type="text"
                        value={line.reference}
                        onChange={e => updateLine(index, 'reference', e.target.value)}
                        placeholder="Reference / TXN ID"
                        className="w-full h-full bg-white/5 border border-white/10 text-white text-sm py-3 px-3 rounded-lg outline-none focus:border-amber-400/50 placeholder-white/20"
                      />
                    </div>
                  )}
                  {splitLines.length > 1 && (
                    <button
                      onClick={() => removeLine(index)}
                      className="text-white/20 hover:text-red-400 transition-colors px-2"
                    >
                      ✕
                    </button>
                  )}
                </div>

                {/* Method-specific context */}
                {line.method === 'TRANSFER' && (
                  <div className="bg-amber-400/10 border border-amber-400/20 rounded-lg px-3 py-2">
                    <p className="text-xs text-amber-300">
                      Transfer payments are recorded as <strong>PENDING</strong> until you confirm receipt.
                    </p>
                  </div>
                )}
              </div>
            ))}

            {/* Summary & CTA */}
            {splitLines.length > 1 && (
              <div className="flex justify-between text-sm py-2 px-1">
                <span className="text-white/40">This payment total</span>
                <span className={`font-mono font-bold ${isOverpaid ? 'text-red-400' : 'text-white'}`}>
                  ₦{splitTotal.toLocaleString()}
                </span>
              </div>
            )}
            {isOverpaid && (
              <p className="text-xs text-red-400 text-center">
                ⚠️ Payment exceeds balance by ₦{(amountPaid + splitTotal - totalAmount).toLocaleString()}
              </p>
            )}

            <button
              onClick={handleRecordAll}
              disabled={splitTotal <= 0 || recordPaymentMutation.isPending}
              className="w-full py-4 bg-amber-400 text-[#0F172A] font-bold tracking-wider rounded-xl active:scale-[0.98] transition-all disabled:opacity-40 text-base"
            >
              {recordPaymentMutation.isPending ? 'RECORDING...' : `RECORD PAYMENT — ₦${splitTotal.toLocaleString()}`}
            </button>
          </div>
        )}

        {/* ── Fully Paid CTA ── */}
        {isFullyPaid && (
          <div className="text-center space-y-4 py-4">
            <div className="text-6xl">✅</div>
            <p className="text-xl font-bold text-emerald-400">Order Fully Paid</p>
            <button
              onClick={() => router.push('/pos')}
              className="w-full py-4 bg-white/10 hover:bg-white/15 text-white font-bold tracking-wider rounded-xl transition-colors"
            >
              CLOSE & RETURN TO FLOOR
            </button>
          </div>
        )}

      </div>
    </div>
  );
}
