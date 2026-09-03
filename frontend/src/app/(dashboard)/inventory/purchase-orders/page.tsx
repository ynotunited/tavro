'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { sanitizeString } from '@/lib/sanitize';

interface Supplier { id: number; name: string; }
interface InventoryItem { id: number; name: string; unit_of_measure: string; cost_per_unit: string; }

interface POItem {
  id: number;
  inventory_item_id: number;
  qty_ordered: string;
  qty_received: string;
  unit_cost: string;
  inventoryItem: { name: string; unit_of_measure: string };
}

interface PurchaseOrder {
  id: number;
  status: string;
  reference: string | null;
  total_cost: string;
  created_at: string;
  received_at: string | null;
  supplier?: { name: string } | null;
  orderedBy?: { name: string } | null;
  items: POItem[];
}

interface DraftLine {
  inventory_item_id: number | '';
  qty_ordered: string;
  unit_cost: string;
}

const STATUS_COLORS: Record<string, string> = {
  DRAFT:    'bg-gray-100 text-gray-600',
  SUBMITTED:'bg-amber-100 text-amber-700',
  RECEIVED: 'bg-emerald-100 text-emerald-700',
};

export default function PurchaseOrdersPage() {
  const queryClient = useQueryClient();
  const [view, setView] = useState<'list' | 'new' | 'receive'>('list');
  const [selectedPO, setSelectedPO] = useState<PurchaseOrder | null>(null);
  const [supplierId, setSupplierId] = useState<number | ''>('');
  const [reference, setReference] = useState('');
  const [draftLines, setDraftLines] = useState<DraftLine[]>([
    { inventory_item_id: '', qty_ordered: '', unit_cost: '' },
  ]);
  const [receiveQtys, setReceiveQtys] = useState<Record<number, string>>({});

  const { data: pos = [], isLoading } = useQuery<PurchaseOrder[]>({
    queryKey: ['purchase-orders'],
    queryFn: async () => (await api.get('/purchase-orders')).data.data,
  });

  const { data: suppliers = [] } = useQuery<Supplier[]>({
    queryKey: ['suppliers'],
    queryFn: async () => (await api.get('/suppliers')).data.data,
  });

  const { data: inventoryItems = [] } = useQuery<InventoryItem[]>({
    queryKey: ['inventory'],
    queryFn: async () => (await api.get('/inventory')).data.data,
  });

  const createPOMutation = useMutation({
    mutationFn: async () => api.post('/purchase-orders', {
      supplier_id: supplierId || undefined,
      reference: reference ? sanitizeString(reference) : undefined,
      items: draftLines.filter(l => l.inventory_item_id).map(l => ({
        inventory_item_id: l.inventory_item_id,
        qty_ordered: parseFloat(l.qty_ordered),
        unit_cost: parseFloat(l.unit_cost),
      })),
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      setView('list');
      setDraftLines([{ inventory_item_id: '', qty_ordered: '', unit_cost: '' }]);
      setSupplierId('');
      setReference('');
    },
  });

  const receiveMutation = useMutation({
    mutationFn: async () => api.post(`/purchase-orders/${selectedPO!.id}/receive`, {
      items: selectedPO!.items.map(item => ({
        purchase_order_item_id: item.id,
        qty_received: parseFloat(receiveQtys[item.id] ?? item.qty_ordered),
      })),
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['purchase-orders'] });
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      setView('list');
      setSelectedPO(null);
    },
  });

  const updateLine = (idx: number, field: keyof DraftLine, value: string | number) => {
    setDraftLines(prev => prev.map((l, i) => i === idx ? { ...l, [field]: value } : l));
  };

  const draftTotal = draftLines.reduce((sum, l) => sum + (parseFloat(l.qty_ordered) || 0) * (parseFloat(l.unit_cost) || 0), 0);

  // ─── New PO Form ──────────────────────────────────────────────────────────
  if (view === 'new') {
    return (
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-4">
          <button onClick={() => setView('list')} className="text-gray-400 hover:text-charcoal transition-colors text-xl">←</button>
          <h1 className="text-2xl font-bold text-charcoal">New Purchase Order</h1>
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
            <select value={supplierId} onChange={e => setSupplierId(Number(e.target.value) || '')} className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-amber bg-white">
              <option value="">No supplier</option>
              {suppliers.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Reference / Invoice #</label>
            <input value={reference} onChange={e => setReference(e.target.value)} placeholder="Optional" className="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-amber" />
          </div>
        </div>

        <div className="space-y-3">
          <div className="grid grid-cols-12 gap-2 text-xs font-semibold text-gray-500 uppercase px-1">
            <span className="col-span-5">Item</span>
            <span className="col-span-3 text-right">Qty</span>
            <span className="col-span-3 text-right">Unit Cost (₦)</span>
            <span className="col-span-1"></span>
          </div>
          {draftLines.map((line, idx) => (
            <div key={idx} className="grid grid-cols-12 gap-2 items-center">
              <div className="col-span-5">
                <select
                  value={line.inventory_item_id}
                  onChange={e => updateLine(idx, 'inventory_item_id', Number(e.target.value) || '')}
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-amber bg-white"
                >
                  <option value="">Select item...</option>
                  {inventoryItems.map(i => <option key={i.id} value={i.id}>{i.name}</option>)}
                </select>
              </div>
              <div className="col-span-3">
                <input type="number" value={line.qty_ordered} onChange={e => updateLine(idx, 'qty_ordered', e.target.value)} placeholder="0" className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-right font-mono outline-none focus:border-amber" />
              </div>
              <div className="col-span-3">
                <input type="number" value={line.unit_cost} onChange={e => updateLine(idx, 'unit_cost', e.target.value)} placeholder="0.00" className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-right font-mono outline-none focus:border-amber" />
              </div>
              <div className="col-span-1 flex justify-center">
                {draftLines.length > 1 && (
                  <button onClick={() => setDraftLines(p => p.filter((_, i) => i !== idx))} className="text-gray-300 hover:text-red-400 transition-colors">✕</button>
                )}
              </div>
            </div>
          ))}
          <button onClick={() => setDraftLines(p => [...p, { inventory_item_id: '', qty_ordered: '', unit_cost: '' }])} className="text-sm text-amber font-medium hover:text-amber/70 transition-colors">
            + Add Line Item
          </button>
        </div>

        <div className="flex justify-between items-center border-t pt-4">
          <div>
            <p className="text-sm text-gray-500">Estimated Total</p>
            <p className="text-2xl font-mono font-bold text-charcoal">₦{draftTotal.toLocaleString()}</p>
          </div>
          <button
            onClick={() => createPOMutation.mutate()}
            disabled={createPOMutation.isPending || draftLines.every(l => !l.inventory_item_id)}
            className="px-6 py-3 bg-amber text-charcoal font-bold rounded-xl disabled:opacity-40 hover:bg-amber/90 transition-colors"
          >
            {createPOMutation.isPending ? 'Creating...' : 'Create Purchase Order'}
          </button>
        </div>
      </div>
    );
  }

  // ─── Receive PO Form ──────────────────────────────────────────────────────
  if (view === 'receive' && selectedPO) {
    return (
      <div className="max-w-2xl mx-auto space-y-6">
        <div className="flex items-center gap-4">
          <button onClick={() => { setView('list'); setSelectedPO(null); }} className="text-gray-400 hover:text-charcoal transition-colors text-xl">←</button>
          <div>
            <h1 className="text-2xl font-bold text-charcoal">Receive PO #{selectedPO.id}</h1>
            <p className="text-sm text-gray-400">{selectedPO.supplier?.name ?? 'No supplier'} · {selectedPO.reference ?? 'No reference'}</p>
          </div>
        </div>

        <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700">
          Enter the actual quantities received. Stock will be updated automatically.
        </div>

        <div className="space-y-3">
          {selectedPO.items.map(item => (
            <div key={item.id} className="bg-white border border-gray-100 rounded-xl p-4 flex items-center gap-4">
              <div className="flex-1">
                <p className="font-semibold text-charcoal">{item.inventoryItem.name}</p>
                <p className="text-xs text-gray-400">Ordered: {Number(item.qty_ordered).toFixed(2)} {item.inventoryItem.unit_of_measure} · ₦{Number(item.unit_cost).toLocaleString()}/unit</p>
              </div>
              <div className="w-32">
                <input
                  type="number"
                  value={receiveQtys[item.id] ?? item.qty_ordered}
                  onChange={e => setReceiveQtys(p => ({ ...p, [item.id]: e.target.value }))}
                  className="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-lg font-mono text-center outline-none focus:border-amber"
                  inputMode="decimal"
                />
              </div>
            </div>
          ))}
        </div>

        <button
          onClick={() => receiveMutation.mutate()}
          disabled={receiveMutation.isPending}
          className="w-full py-4 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition-colors disabled:opacity-50"
        >
          {receiveMutation.isPending ? 'Receiving...' : 'Confirm Receipt & Update Stock'}
        </button>
      </div>
    );
  }

  // ─── PO List ──────────────────────────────────────────────────────────────
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Purchase Orders</h1>
          <p className="text-sm text-gray-500">{pos.length} orders</p>
        </div>
        <button onClick={() => setView('new')} className="px-4 py-2 bg-amber text-charcoal font-bold text-sm rounded-lg hover:bg-amber/90 transition-colors">
          + New PO
        </button>
      </div>

      {isLoading ? (
        <div className="text-center text-gray-400 py-12">Loading...</div>
      ) : (
        <div className="space-y-3">
          {pos.map(po => (
            <div key={po.id} className="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
              <div className="flex justify-between items-start gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="font-semibold text-charcoal">PO #{po.id}</p>
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded ${STATUS_COLORS[po.status] ?? ''}`}>{po.status}</span>
                  </div>
                  <p className="text-sm text-gray-500 mt-0.5">{po.supplier?.name ?? 'No supplier'}{po.reference ? ` · ${po.reference}` : ''}</p>
                  <p className="text-xs text-gray-400 mt-1">{po.items.length} items · ₦{Number(po.total_cost).toLocaleString()}</p>
                </div>
                {po.status === 'DRAFT' && (
                  <button
                    onClick={() => { setSelectedPO(po); setReceiveQtys({}); setView('receive'); }}
                    className="shrink-0 text-sm font-medium text-emerald-600 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition-colors"
                  >
                    Receive
                  </button>
                )}
              </div>
            </div>
          ))}
          {pos.length === 0 && (
            <div className="text-center text-gray-400 py-16">
              <p className="text-4xl mb-3">📦</p>
              <p className="font-medium">No purchase orders yet</p>
              <p className="text-sm">Create your first PO to start tracking stock receiving.</p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
