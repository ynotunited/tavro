'use client';

import { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { useRouter } from 'next/navigation';

interface Shift {
  id: number;
  status: string;
  opening_cash: string;
  closing_cash_actual: string | null;
  expected_cash: string | null;
  cash_variance: string | null;
  variance_reason: string | null;
  opened_at: string;
  closed_at: string | null;
  approved_at: string | null;
}

const DENOMINATIONS = [
  { value: 1000, label: '₦1000' },
  { value: 500, label: '₦500' },
  { value: 200, label: '₦200' },
  { value: 100, label: '₦100' },
  { value: 50, label: '₦50' },
  { value: 20, label: '₦20' },
  { value: 10, label: '₦10' },
  { value: 5, label: '₦5' },
];

export default function ShiftsPage() {
  const queryClient = useQueryClient();
  const router = useRouter();

  const [openingCash, setOpeningCash] = useState('');
  
  // Closing states
  const [isClosing, setIsClosing] = useState(false);
  const [counts, setCounts] = useState<Record<number, number>>({});
  const [varianceReason, setVarianceReason] = useState('');

  const { data: activeShift, isLoading: isLoadingActive } = useQuery<Shift | null>({
    queryKey: ['shifts', 'active'],
    queryFn: async () => (await api.get('/shifts/active')).data.data,
  });

  const { data: shiftHistory = [] } = useQuery<Shift[]>({
    queryKey: ['shifts', 'history'],
    queryFn: async () => (await api.get('/shifts')).data.data,
  });

  const { data: closeTotals } = useQuery({
    queryKey: ['shifts', activeShift?.id, 'prepare-close'],
    queryFn: async () => (await api.post(`/shifts/${activeShift!.id}/prepare-close`)).data,
    enabled: !!activeShift && isClosing,
  });

  const openShiftMutation = useMutation({
    mutationFn: async () => api.post('/shifts', { opening_cash: parseFloat(openingCash || '0') }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shifts'] });
      setOpeningCash('');
    },
  });

  const closeShiftMutation = useMutation({
    mutationFn: async (actualCash: number) => api.post(`/shifts/${activeShift!.id}/close`, {
      actual_cash: actualCash,
      variance_reason: varianceReason || undefined,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shifts'] });
      setIsClosing(false);
      setCounts({});
      setVarianceReason('');
    },
  });

  const updateCount = (val: number, delta: number) => {
    setCounts(prev => {
      const current = prev[val] || 0;
      const next = Math.max(0, current + delta);
      return { ...prev, [val]: next };
    });
  };

  const actualCashTotal = useMemo(() => {
    return Object.entries(counts).reduce((sum, [val, count]) => sum + (Number(val) * count), 0);
  }, [counts]);

  if (isLoadingActive) return <div className="p-8 text-center text-gray-500">Loading...</div>;

  // ─── CLOSE SHIFT FLOW ──────────────────────────────────────────────────
  if (activeShift && isClosing && closeTotals) {
    const expected = Number(closeTotals.expected_cash);
    const variance = actualCashTotal - expected;
    const isVariance = Math.abs(variance) > 0.01;
    const requiresApproval = Math.abs(variance) > 500;

    return (
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-4">
          <button onClick={() => setIsClosing(false)} className="text-gray-400 hover:text-charcoal text-xl">←</button>
          <h1 className="text-2xl font-bold text-charcoal">Close Shift</h1>
        </div>

        {/* Totals Summary */}
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-gray-50 border border-gray-100 rounded-xl p-4 text-center">
            <p className="text-xs text-gray-500 uppercase tracking-widest font-semibold mb-1">Expected Cash</p>
            <p className="text-2xl font-mono font-bold text-charcoal">₦{expected.toLocaleString()}</p>
          </div>
          <div className="bg-white border-2 border-amber rounded-xl p-4 text-center shadow-sm">
            <p className="text-xs text-amber-700 uppercase tracking-widest font-semibold mb-1">Actual Cash (Counted)</p>
            <p className="text-2xl font-mono font-bold text-amber-600">₦{actualCashTotal.toLocaleString()}</p>
          </div>
        </div>

        {/* Variance Display */}
        {isVariance && (
          <div className={`rounded-xl p-4 text-center ${variance < 0 ? 'bg-red-50 border-red-100 text-red-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700'}`}>
            <p className="font-bold">
              {variance < 0 ? 'Shortage' : 'Overage'}: ₦{Math.abs(variance).toLocaleString()}
            </p>
            {requiresApproval && <p className="text-sm mt-1">⚠️ Requires manager approval</p>}
          </div>
        )}

        {/* Denomination Counter */}
        <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between">
            <p className="font-semibold text-charcoal">Cash Count</p>
            <button onClick={() => setCounts({})} className="text-sm text-amber font-medium">Reset</button>
          </div>
          <div className="p-4 grid gap-3">
            {DENOMINATIONS.map(den => {
              const count = counts[den.value] || 0;
              const lineTotal = count * den.value;
              return (
                <div key={den.value} className="flex items-center justify-between gap-4">
                  <div className="w-20 font-bold text-charcoal">{den.label}</div>
                  
                  <div className="flex items-center gap-3">
                    <button onClick={() => updateCount(den.value, -1)} className="w-10 h-10 rounded-full bg-gray-100 text-gray-600 font-bold text-xl active:scale-95 flex items-center justify-center">−</button>
                    <div className="w-12 text-center font-mono font-bold text-lg">{count}</div>
                    <button onClick={() => updateCount(den.value, 1)} className="w-10 h-10 rounded-full bg-gray-100 text-gray-600 font-bold text-xl active:scale-95 flex items-center justify-center">+</button>
                  </div>
                  
                  <div className="w-24 text-right font-mono font-medium text-gray-500">
                    {lineTotal > 0 ? `₦${lineTotal.toLocaleString()}` : '—'}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Variance Reason */}
        {isVariance && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Variance Reason *</label>
            <input
              type="text"
              value={varianceReason}
              onChange={e => setVarianceReason(e.target.value)}
              placeholder="Explain the discrepancy..."
              className="w-full border border-gray-200 rounded-lg px-4 py-3 outline-none focus:border-amber"
            />
          </div>
        )}

        <button
          onClick={() => closeShiftMutation.mutate(actualCashTotal)}
          disabled={closeShiftMutation.isPending || (isVariance && !varianceReason)}
          className="w-full py-4 bg-charcoal text-white font-bold rounded-xl active:scale-[0.98] transition-all disabled:opacity-50"
        >
          {closeShiftMutation.isPending ? 'Closing...' : (requiresApproval ? 'Submit for Approval' : 'Close Shift & Confirm Actuals')}
        </button>
      </div>
    );
  }

  // ─── ACTIVE SHIFT OR OPEN NEW ──────────────────────────────────────────
  return (
    <div className="max-w-2xl mx-auto space-y-8">
      
      {activeShift ? (
        <div className="bg-charcoal text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
          <div className="absolute top-0 right-0 p-4 opacity-10 text-6xl">⏱️</div>
          <div className="relative z-10 space-y-4">
            <div>
              <span className={`text-xs font-bold px-2 py-1 rounded-md bg-white/20 uppercase tracking-wider`}>
                {activeShift.status}
              </span>
              <h2 className="text-2xl font-bold mt-3">Current Shift</h2>
              <p className="text-white/60 text-sm">Opened at {new Date(activeShift.opened_at).toLocaleTimeString()}</p>
            </div>
            
            <div className="pt-4 border-t border-white/10">
              <p className="text-sm text-white/50">Opening Cash</p>
              <p className="font-mono font-bold text-xl text-amber">₦{Number(activeShift.opening_cash).toLocaleString()}</p>
            </div>

            {activeShift.status === 'CLOSING' ? (
              <div className="bg-amber/20 text-amber-100 p-4 rounded-xl text-sm border border-amber/30">
                <p className="font-bold">Pending Manager Approval</p>
                <p>This shift had a high variance and is waiting for a manager to review it.</p>
              </div>
            ) : (
              <button
                onClick={() => setIsClosing(true)}
                className="w-full py-3 bg-amber text-charcoal font-bold rounded-xl hover:bg-amber/90 transition-colors"
              >
                Close Shift
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm space-y-4">
          <div>
            <h2 className="text-2xl font-bold text-charcoal">Open New Shift</h2>
            <p className="text-gray-500 text-sm">Enter the float / opening cash in the till.</p>
          </div>
          <div>
            <input
              type="number"
              value={openingCash}
              onChange={e => setOpeningCash(e.target.value)}
              placeholder="₦ 0.00"
              className="w-full border-2 border-gray-200 rounded-xl px-4 py-4 text-3xl font-mono font-bold outline-none focus:border-amber text-charcoal"
            />
          </div>
          <button
            onClick={() => openShiftMutation.mutate()}
            disabled={openShiftMutation.isPending || !openingCash}
            className="w-full py-4 bg-charcoal text-white font-bold rounded-xl active:scale-[0.98] transition-all disabled:opacity-50"
          >
            {openShiftMutation.isPending ? 'Opening...' : 'Start Shift'}
          </button>
        </div>
      )}

      {/* ─── SHIFT HISTORY ────────────────────────────────────────────────── */}
      <div className="space-y-4">
        <h3 className="text-lg font-bold text-charcoal">Recent Shifts</h3>
        <div className="bg-white border border-gray-100 rounded-xl overflow-hidden divide-y divide-gray-50">
          {shiftHistory.map(shift => (
            <div key={shift.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-semibold text-charcoal">Shift #{shift.id}</span>
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase ${
                    shift.status === 'CLOSED' ? 'bg-gray-100 text-gray-500' :
                    shift.status === 'CLOSING' ? 'bg-amber-100 text-amber-700' :
                    'bg-emerald-100 text-emerald-700'
                  }`}>
                    {shift.status}
                  </span>
                </div>
                <p className="text-xs text-gray-500">
                  {new Date(shift.opened_at).toLocaleDateString()} · {new Date(shift.opened_at).toLocaleTimeString()} 
                  {shift.closed_at && ` → ${new Date(shift.closed_at).toLocaleTimeString()}`}
                </p>
              </div>
              
              {shift.status === 'CLOSED' && (
                <div className="flex gap-6 text-right">
                  <div>
                    <p className="text-xs text-gray-400">Actual</p>
                    <p className="font-mono font-bold text-sm text-charcoal">₦{Number(shift.closing_cash_actual).toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-xs text-gray-400">Variance</p>
                    <p className={`font-mono font-bold text-sm ${Number(shift.cash_variance) < 0 ? 'text-red-500' : Number(shift.cash_variance) > 0 ? 'text-emerald-500' : 'text-gray-300'}`}>
                      {Number(shift.cash_variance) === 0 ? '—' : `₦${Number(shift.cash_variance).toLocaleString()}`}
                    </p>
                  </div>
                </div>
              )}
            </div>
          ))}
          {shiftHistory.length === 0 && (
            <div className="p-8 text-center text-gray-400">No shift history found.</div>
          )}
        </div>
      </div>

    </div>
  );
}
