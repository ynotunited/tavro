'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { trimStrings } from '@/lib/sanitize';

interface InventoryItem {
  id: number;
  name: string;
  sku: string | null;
  category: string | null;
  unit_of_measure: string;
  cost_per_unit: string;
  current_stock: string;
  min_level: string;
  track_inventory: boolean;
  supplier?: { name: string } | null;
}

const getStockStatus = (item: InventoryItem) => {
  const current = Number(item.current_stock);
  const min = Number(item.min_level);
  if (current <= 0) return { label: 'Out of Stock', color: 'text-red-400 bg-red-500/10 border-red-500/20' };
  if (min > 0 && current <= min) return { label: 'Low Stock', color: 'text-amber-400 bg-amber-500/10 border-amber-500/20' };
  return { label: 'In Stock', color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' };
};

export default function InventoryPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [showAdjustModal, setShowAdjustModal] = useState<InventoryItem | null>(null);
  const [adjustQty, setAdjustQty] = useState('');
  const [adjustNotes, setAdjustNotes] = useState('');
  const [newItem, setNewItem] = useState<Record<string, string>>({ name: '', category: '', unit_of_measure: 'piece', cost_per_unit: '', min_level: '' });

  const { data: items = [], isLoading } = useQuery<InventoryItem[]>({
    queryKey: ['inventory'],
    queryFn: async () => (await api.get('/inventory')).data.data,
  });

  const categories = [...new Set(items.map(i => i.category).filter(Boolean))] as string[];

  const filtered = items.filter(item => {
    if (selectedCategory && item.category !== selectedCategory) return false;
    if (search && !item.name.toLowerCase().includes(search.toLowerCase())) return false;
    return true;
  });

  const addItemMutation = useMutation({
    mutationFn: async () => api.post('/inventory', trimStrings(newItem)),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['inventory'] }); setShowAddModal(false); setNewItem({ name: '', category: '', unit_of_measure: 'piece', cost_per_unit: '', min_level: '' }); },
  });

  const adjustMutation = useMutation({
    mutationFn: async () => api.post('/inventory/adjust', {
      inventory_item_id: showAdjustModal!.id,
      quantity_change: parseFloat(adjustQty),
      notes: adjustNotes.trim(),
    }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['inventory'] }); setShowAdjustModal(null); setAdjustQty(''); setAdjustNotes(''); },
  });

  if (isLoading) return <div className="flex items-center justify-center h-full text-gray-400">Loading inventory...</div>;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Inventory</h1>
          <p className="text-sm text-gray-500">{items.length} items tracked</p>
        </div>
        <div className="flex gap-3">
          <a href="/inventory/count" className="px-4 py-2 border border-charcoal/20 text-charcoal font-medium text-sm rounded-lg hover:bg-charcoal/5 transition-colors">📋 Stock Count</a>
          <a href="/inventory/wastage" className="px-4 py-2 border border-charcoal/20 text-charcoal font-medium text-sm rounded-lg hover:bg-charcoal/5 transition-colors">🗑 Wastage</a>
          <a href="/inventory/purchase-orders" className="px-4 py-2 border border-charcoal/20 text-charcoal font-medium text-sm rounded-lg hover:bg-charcoal/5 transition-colors">📦 PO</a>
          <button onClick={() => setShowAddModal(true)} className="px-4 py-2 bg-amber text-charcoal font-bold text-sm rounded-lg hover:bg-amber/90 transition-colors">+ Add Item</button>
        </div>
      </div>

      {/* Filters */}
      <div className="flex gap-3 flex-wrap">
        <input value={search} onChange={e => setSearch(e.target.value)} placeholder="🔍 Search items..." className="border border-gray-200 rounded-lg px-4 py-2 text-sm outline-none focus:border-amber w-56" />
        <select value={selectedCategory} onChange={e => setSelectedCategory(e.target.value)} className="border border-gray-200 rounded-lg px-4 py-2 text-sm outline-none focus:border-amber bg-white">
          <option value="">All Categories</option>
          {categories.map(c => <option key={c} value={c}>{c}</option>)}
        </select>
      </div>

      {/* Stats Strip */}
      <div className="grid grid-cols-3 gap-4">
        {[
          { label: 'Total Items', value: items.length, color: 'text-charcoal' },
          { label: 'Low Stock', value: items.filter(i => { const c = Number(i.current_stock); const m = Number(i.min_level); return c > 0 && m > 0 && c <= m; }).length, color: 'text-amber-500' },
          { label: 'Out of Stock', value: items.filter(i => Number(i.current_stock) <= 0).length, color: 'text-red-500' },
        ].map(stat => (
          <div key={stat.label} className="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
            <p className="text-xs text-gray-400 uppercase tracking-wide">{stat.label}</p>
            <p className={`text-3xl font-bold font-mono mt-1 ${stat.color}`}>{stat.value}</p>
          </div>
        ))}
      </div>

      {/* Desktop Table / Mobile Cards */}
      <div className="hidden md:block bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-100 text-charcoal font-semibold text-xs uppercase tracking-wide">
            <tr>
              <th className="px-6 py-4 text-left">Item</th>
              <th className="px-6 py-4 text-left">Category</th>
              <th className="px-6 py-4 text-right">Stock</th>
              <th className="px-6 py-4 text-right">Cost/Unit</th>
              <th className="px-6 py-4 text-center">Status</th>
              <th className="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-50">
            {filtered.map(item => {
              const status = getStockStatus(item);
              return (
                <tr key={item.id} className="hover:bg-gray-50/60 transition-colors">
                  <td className="px-6 py-4">
                    <p className="font-medium text-charcoal">{item.name}</p>
                    {item.sku && <p className="text-xs text-gray-400 font-mono">{item.sku}</p>}
                  </td>
                  <td className="px-6 py-4 text-gray-500">{item.category ?? '—'}</td>
                  <td className="px-6 py-4 text-right font-mono font-bold">
                    {Number(item.current_stock).toFixed(2)} <span className="font-normal text-gray-400 text-xs">{item.unit_of_measure}</span>
                  </td>
                  <td className="px-6 py-4 text-right text-gray-600 font-mono">₦{Number(item.cost_per_unit).toLocaleString()}</td>
                  <td className="px-6 py-4 text-center">
                    <span className={`text-xs font-semibold px-2 py-1 rounded border ${status.color}`}>{status.label}</span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button onClick={() => { setShowAdjustModal(item); setAdjustQty(''); }} className="text-xs text-amber hover:text-amber/70 font-medium transition-colors">Adjust</button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
        {filtered.length === 0 && <div className="p-8 text-center text-gray-400">No items found.</div>}
      </div>

      {/* Mobile Cards */}
      <div className="md:hidden space-y-3">
        {filtered.map(item => {
          const status = getStockStatus(item);
          return (
            <div key={item.id} className="bg-white border border-gray-100 rounded-xl p-4 shadow-sm flex justify-between items-center gap-4">
              <div className="flex-1 min-w-0">
                <p className="font-semibold text-charcoal truncate">{item.name}</p>
                <p className="text-xs text-gray-400">{item.category}</p>
                <p className="font-mono font-bold text-lg mt-1">{Number(item.current_stock).toFixed(1)} <span className="text-xs font-normal text-gray-400">{item.unit_of_measure}</span></p>
              </div>
              <div className="text-right shrink-0 space-y-2">
                <span className={`text-xs font-semibold px-2 py-1 rounded border block ${status.color}`}>{status.label}</span>
                <button onClick={() => { setShowAdjustModal(item); setAdjustQty(''); }} className="text-xs text-amber font-medium">Adjust →</button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Add Item Modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-end md:items-center justify-center p-4">
          <div className="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl space-y-4">
            <h2 className="text-lg font-bold text-charcoal">Add Inventory Item</h2>
            {[
              { label: 'Name *', key: 'name', type: 'text', placeholder: 'e.g. Hendricks Gin' },
              { label: 'Category', key: 'category', type: 'text', placeholder: 'e.g. Spirits' },
              { label: 'Unit of Measure *', key: 'unit_of_measure', type: 'text', placeholder: 'e.g. bottle, ml, g' },
              { label: 'Cost per Unit (₦)', key: 'cost_per_unit', type: 'number', placeholder: '0.00' },
              { label: 'Min Level (reorder point)', key: 'min_level', type: 'number', placeholder: '0' },
            ].map(({ label, key, type, placeholder }) => (
              <div key={key}>
                <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <input type={type} value={newItem[key]} onChange={e => setNewItem(p => ({ ...p, [key]: e.target.value }))} placeholder={placeholder} className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-amber" />
              </div>
            ))}
            <div className="flex gap-3 pt-2">
              <button onClick={() => setShowAddModal(false)} className="flex-1 py-3 border border-gray-200 text-gray-600 rounded-lg font-medium text-sm">Cancel</button>
              <button onClick={() => addItemMutation.mutate()} disabled={addItemMutation.isPending || !newItem.name} className="flex-1 py-3 bg-amber text-charcoal rounded-lg font-bold text-sm disabled:opacity-50">Save</button>
            </div>
          </div>
        </div>
      )}

      {/* Adjust Modal */}
      {showAdjustModal && (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-end md:items-center justify-center p-4">
          <div className="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl space-y-4">
            <h2 className="text-lg font-bold text-charcoal">Adjust: {showAdjustModal.name}</h2>
            <p className="text-sm text-gray-500">Current stock: <strong className="font-mono">{Number(showAdjustModal.current_stock).toFixed(2)} {showAdjustModal.unit_of_measure}</strong></p>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Quantity Change (+ or −)</label>
              <input type="number" value={adjustQty} onChange={e => setAdjustQty(e.target.value)} placeholder="e.g. -2 or +5" className="w-full border border-gray-200 rounded-lg px-4 py-3 text-xl font-mono text-center outline-none focus:border-amber" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Reason (required)</label>
              <input type="text" value={adjustNotes} onChange={e => setAdjustNotes(e.target.value)} placeholder="e.g. Damaged on delivery" className="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-amber" />
            </div>
            <div className="flex gap-3 pt-2">
              <button onClick={() => setShowAdjustModal(null)} className="flex-1 py-3 border border-gray-200 text-gray-600 rounded-lg font-medium text-sm">Cancel</button>
              <button onClick={() => adjustMutation.mutate()} disabled={adjustMutation.isPending || !adjustQty || !adjustNotes} className="flex-1 py-3 bg-amber text-charcoal rounded-lg font-bold text-sm disabled:opacity-50">Apply Adjustment</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
