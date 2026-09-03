'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { sanitizeString } from '@/lib/sanitize';

interface InventoryItem {
  id: number;
  name: string;
  unit_of_measure: string;
  cost_per_unit: string;
  current_stock: string;
  category: string | null;
}

const WASTAGE_TYPES = [
  { value: 'spoilage',      label: '🥴 Spoilage',       desc: 'Food/drink gone bad' },
  { value: 'breakage',      label: '💔 Breakage',       desc: 'Broken bottles or containers' },
  { value: 'over-pour',     label: '🍷 Over-pour',      desc: 'Excess poured for a drink' },
  { value: 'kitchen-error', label: '🔥 Kitchen Error',  desc: 'Wrong item prepared' },
  { value: 'wrong-order',   label: '🔄 Wrong Order',    desc: 'Sent to wrong table' },
  { value: 'expired',       label: '📅 Expired',        desc: 'Past expiry date' },
  { value: 'other',         label: '📋 Other',          desc: 'Other reason' },
];

const HIGH_VALUE_THRESHOLD = 5000;

export default function WastagePage() {
  const queryClient = useQueryClient();
  const [selectedItemId, setSelectedItemId] = useState<number | ''>('');
  const [quantity, setQuantity] = useState('');
  const [type, setType] = useState('');
  const [notes, setNotes] = useState('');
  const [success, setSuccess] = useState(false);

  const { data: items = [] } = useQuery<InventoryItem[]>({
    queryKey: ['inventory'],
    queryFn: async () => (await api.get('/inventory')).data.data,
  });

  const selectedItem = items.find(i => i.id === selectedItemId);
  const estimatedCost = selectedItem ? Number(selectedItem.cost_per_unit) * parseFloat(quantity || '0') : 0;
  const requiresApproval = estimatedCost >= HIGH_VALUE_THRESHOLD;

  const wastageMutation = useMutation({
    mutationFn: async () => api.post('/inventory/wastage', {
      inventory_item_id: selectedItemId,
      quantity: parseFloat(quantity),
      type,
      notes: notes ? sanitizeString(notes) : undefined,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      setSuccess(true);
      setSelectedItemId('');
      setQuantity('');
      setType('');
      setNotes('');
    },
  });

  if (success) {
    return (
      <div className="max-w-md mx-auto py-16 text-center space-y-4">
        <div className="text-6xl">{requiresApproval ? '⏳' : '✅'}</div>
        <h2 className="text-2xl font-bold text-charcoal">{requiresApproval ? 'Pending Approval' : 'Wastage Recorded'}</h2>
        <p className="text-gray-500">{requiresApproval ? 'A manager must approve this high-value wastage before stock is deducted.' : 'Stock has been updated automatically.'}</p>
        <button onClick={() => setSuccess(false)} className="w-full py-4 bg-amber text-charcoal font-bold rounded-xl">Record Another</button>
      </div>
    );
  }

  return (
    <div className="max-w-lg mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Record Wastage</h1>
        <p className="text-sm text-gray-500 mt-1">High-value items (≥ ₦{HIGH_VALUE_THRESHOLD.toLocaleString()}) require manager approval.</p>
      </div>

      {/* Item Selector */}
      <div>
        <label className="block text-sm font-semibold text-charcoal mb-2">Item *</label>
        <select
          value={selectedItemId}
          onChange={e => setSelectedItemId(Number(e.target.value) || '')}
          className="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-charcoal outline-none focus:border-amber bg-white"
        >
          <option value="">Select an inventory item...</option>
          {items.map(item => (
            <option key={item.id} value={item.id}>
              {item.name} ({Number(item.current_stock).toFixed(1)} {item.unit_of_measure} in stock)
            </option>
          ))}
        </select>
      </div>

      {/* Quantity */}
      <div>
        <label className="block text-sm font-semibold text-charcoal mb-2">
          Quantity {selectedItem && `(${selectedItem.unit_of_measure})`} *
        </label>
        <input
          type="number"
          value={quantity}
          onChange={e => setQuantity(e.target.value)}
          placeholder="0"
          inputMode="decimal"
          className="w-full border-2 border-gray-200 rounded-xl px-4 py-4 text-4xl font-mono text-center outline-none focus:border-amber"
        />
        {estimatedCost > 0 && (
          <p className={`text-center text-sm mt-2 font-semibold ${requiresApproval ? 'text-red-500' : 'text-gray-500'}`}>
            Estimated value: ₦{estimatedCost.toLocaleString()} {requiresApproval && '⚠️ Manager approval required'}
          </p>
        )}
      </div>

      {/* Wastage Type */}
      <div>
        <label className="block text-sm font-semibold text-charcoal mb-2">Reason *</label>
        <div className="grid grid-cols-2 gap-2">
          {WASTAGE_TYPES.map(wt => (
            <button
              key={wt.value}
              onClick={() => setType(wt.value)}
              className={`p-3 border-2 rounded-xl text-left transition-colors ${type === wt.value ? 'border-amber bg-amber/5' : 'border-gray-200 hover:border-gray-300'}`}
            >
              <p className="text-sm font-semibold text-charcoal">{wt.label}</p>
              <p className="text-xs text-gray-400">{wt.desc}</p>
            </button>
          ))}
        </div>
      </div>

      {/* Notes */}
      <div>
        <label className="block text-sm font-semibold text-charcoal mb-2">Notes (optional)</label>
        <textarea
          value={notes}
          onChange={e => setNotes(e.target.value)}
          placeholder="Any additional details..."
          rows={2}
          className="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-amber resize-none"
        />
      </div>

      <button
        onClick={() => wastageMutation.mutate()}
        disabled={!selectedItemId || !quantity || !type || wastageMutation.isPending}
        className="w-full py-4 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition-colors disabled:opacity-40 text-base"
      >
        {wastageMutation.isPending ? 'Recording...' : requiresApproval ? 'Submit for Approval' : 'Record Wastage'}
      </button>
    </div>
  );
}
