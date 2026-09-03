'use client';

import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import api from '@/lib/axios';

interface CountEntry {
  id: number;
  inventory_item_id: number;
  expected_qty: string;
  actual_qty: string;
  variance_qty: string;
  inventoryItem: { id: number; name: string; unit_of_measure: string; category: string | null };
}

interface CountSession {
  id: number;
  type: string;
  status: string;
  entries: CountEntry[];
}

export default function StockCountPage() {
  const router = useRouter();
  const [session, setSession] = useState<CountSession | null>(null);
  const [counts, setCounts] = useState<Record<number, string>>({});
  const [countType, setCountType] = useState<'full' | 'bar' | 'category'>('full');
  const [activeEntryId, setActiveEntryId] = useState<number | null>(null);

  const startCountMutation = useMutation({
    mutationFn: async () => api.post('/inventory/counts', { type: countType }),
    onSuccess: (res) => {
      const s: CountSession = res.data.data;
      setSession(s);
      const initial: Record<number, string> = {};
      s.entries.forEach(e => { initial[e.id] = e.expected_qty; });
      setCounts(initial);
    },
  });

  const submitMutation = useMutation({
    mutationFn: async () => {
      // First save all entries
      await api.patch(`/inventory/counts/${session!.id}/entries`, {
        entries: session!.entries.map(e => ({ id: e.id, actual_qty: parseFloat(counts[e.id] ?? e.expected_qty) })),
      });
      return api.post(`/inventory/counts/${session!.id}/submit`);
    },
    onSuccess: () => {
      setSession(s => s ? { ...s, status: 'SUBMITTED' } : s);
    },
  });

  const approveMutation = useMutation({
    mutationFn: async () => api.post(`/inventory/counts/${session!.id}/approve`),
    onSuccess: () => {
      setSession(s => s ? { ...s, status: 'APPROVED' } : s);
    },
  });

  if (!session) {
    return (
      <div className="max-w-md mx-auto space-y-6 py-8">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">New Stock Count</h1>
          <p className="text-sm text-gray-500 mt-1">Select the type of count to perform.</p>
        </div>

        <div className="space-y-3">
          {([
            { value: 'full', label: 'Full Count', desc: 'Count all tracked inventory items' },
            { value: 'bar', label: 'Bar Count', desc: 'Spirits, Mixers, Wine, Beer only' },
            { value: 'category', label: 'Category Count', desc: 'Count a specific category' },
          ] as const).map(opt => (
            <button
              key={opt.value}
              onClick={() => setCountType(opt.value)}
              className={`w-full p-4 border-2 rounded-xl text-left transition-colors ${countType === opt.value ? 'border-amber bg-amber/5' : 'border-gray-200 hover:border-gray-300'}`}
            >
              <p className={`font-semibold ${countType === opt.value ? 'text-amber' : 'text-charcoal'}`}>{opt.label}</p>
              <p className="text-sm text-gray-500">{opt.desc}</p>
            </button>
          ))}
        </div>

        <button
          onClick={() => startCountMutation.mutate()}
          disabled={startCountMutation.isPending}
          className="w-full py-4 bg-charcoal text-white font-bold rounded-xl hover:bg-charcoal/90 transition-colors disabled:opacity-50"
        >
          {startCountMutation.isPending ? 'Starting...' : 'Start Count Session'}
        </button>
      </div>
    );
  }

  if (session.status === 'APPROVED') {
    return (
      <div className="max-w-md mx-auto py-16 text-center space-y-4">
        <div className="text-6xl">✅</div>
        <h2 className="text-2xl font-bold text-charcoal">Count Approved</h2>
        <p className="text-gray-500">Stock levels have been adjusted to match the count.</p>
        <button onClick={() => router.push('/inventory')} className="w-full py-4 bg-amber text-charcoal font-bold rounded-xl">Return to Inventory</button>
      </div>
    );
  }

  return (
    <div className="space-y-4 max-w-2xl mx-auto">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-xl font-bold text-charcoal">Stock Count — {session.type.toUpperCase()}</h1>
          <p className="text-sm text-gray-400">{session.entries.length} items · Status: {session.status}</p>
        </div>
        {session.status === 'DRAFT' && (
          <button onClick={() => submitMutation.mutate()} disabled={submitMutation.isPending} className="px-5 py-2.5 bg-charcoal text-white font-bold rounded-lg text-sm disabled:opacity-50">
            Submit for Approval
          </button>
        )}
        {session.status === 'SUBMITTED' && (
          <button onClick={() => approveMutation.mutate()} disabled={approveMutation.isPending} className="px-5 py-2.5 bg-emerald-500 text-white font-bold rounded-lg text-sm disabled:opacity-50">
            Approve & Apply
          </button>
        )}
      </div>

      <div className="space-y-2">
        {session.entries.map(entry => {
          const actual = parseFloat(counts[entry.id] ?? entry.expected_qty);
          const expected = parseFloat(entry.expected_qty);
          const variance = actual - expected;
          const isActive = activeEntryId === entry.id;

          return (
            <div key={entry.id} className={`bg-white border rounded-xl overflow-hidden transition-all ${isActive ? 'border-amber shadow-md' : 'border-gray-100'}`}>
              <button
                className="w-full p-4 text-left flex justify-between items-center"
                onClick={() => setActiveEntryId(isActive ? null : entry.id)}
                disabled={session.status !== 'DRAFT'}
              >
                <div>
                  <p className="font-semibold text-charcoal">{entry.inventoryItem.name}</p>
                  <p className="text-xs text-gray-400">{entry.inventoryItem.category} · Expected: {expected.toFixed(2)} {entry.inventoryItem.unit_of_measure}</p>
                </div>
                <div className="text-right shrink-0">
                  <p className="font-mono font-bold text-lg">{actual.toFixed(1)}</p>
                  {variance !== 0 && (
                    <p className={`text-xs font-mono font-semibold ${variance < 0 ? 'text-red-500' : 'text-emerald-500'}`}>
                      {variance > 0 ? '+' : ''}{variance.toFixed(2)}
                    </p>
                  )}
                </div>
              </button>

              {isActive && (
                <div className="px-4 pb-4">
                  {/* Mobile numpad-style input */}
                  <input
                    type="number"
                    value={counts[entry.id] ?? entry.expected_qty}
                    onChange={e => setCounts(p => ({ ...p, [entry.id]: e.target.value }))}
                    className="w-full border-2 border-amber rounded-xl px-4 py-4 text-4xl font-mono text-center font-bold outline-none"
                    autoFocus
                    inputMode="decimal"
                  />
                  <p className="text-center text-xs text-gray-400 mt-2">Enter actual counted quantity in {entry.inventoryItem.unit_of_measure}</p>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
