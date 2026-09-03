'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';

interface OpenBottle {
  id: number;
  current_volume: string;
  opened_at: string;
}

interface InventoryItem {
  id: number;
  name: string;
  category: string;
  current_stock: string;
  unit_of_measure: string;
  openBottles: OpenBottle[];
}

export default function BarInventoryPage() {
  const queryClient = useQueryClient();
  const [showOpenModal, setShowOpenModal] = useState(false);
  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [volume, setVolume] = useState('');

  const { data: inventory = [], isLoading } = useQuery<InventoryItem[]>({
    queryKey: ['bar-inventory'],
    queryFn: async () => (await api.get('/bar/inventory/open-bottles')).data.data,
  });

  const openBottleMutation = useMutation({
    mutationFn: async () => 
      api.post('/bar/inventory/open-bottles', { 
        inventory_item_id: selectedItemId, 
        volume: parseFloat(volume) 
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bar-inventory'] });
      setShowOpenModal(false);
      setVolume('');
    },
  });

  if (isLoading) {
    return <div className="p-8">Loading inventory...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Bar Inventory</h1>
          <p className="text-sm text-gray-500">Track open bottles and bar stock levels.</p>
        </div>
      </div>

      <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table className="w-full text-sm text-left">
          <thead className="bg-gray-50 border-b border-gray-200 text-charcoal font-semibold">
            <tr>
              <th className="px-6 py-4">Item Name</th>
              <th className="px-6 py-4">Category</th>
              <th className="px-6 py-4">Stock (Unopened)</th>
              <th className="px-6 py-4">Open Bottles</th>
              <th className="px-6 py-4">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {inventory.map((item) => (
              <tr key={item.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 font-medium">{item.name}</td>
                <td className="px-6 py-4 text-gray-500">{item.category}</td>
                <td className="px-6 py-4">
                  <span className={`font-mono font-medium ${Number(item.current_stock) <= 0 ? 'text-red-500' : ''}`}>
                    {Number(item.current_stock)}
                  </span>
                </td>
                <td className="px-6 py-4">
                  {item.openBottles.length > 0 ? (
                    <div className="space-y-1">
                      {item.openBottles.map(bottle => (
                        <div key={bottle.id} className="text-xs bg-sky-50 text-sky-700 px-2 py-1 rounded inline-block mr-2">
                          <span className="font-bold">{Number(bottle.current_volume)}{item.unit_of_measure}</span> remaining
                        </div>
                      ))}
                    </div>
                  ) : (
                    <span className="text-gray-400 italic text-xs">No open bottles</span>
                  )}
                </td>
                <td className="px-6 py-4">
                  <button
                    onClick={() => {
                      setSelectedItemId(item.id);
                      setShowOpenModal(true);
                    }}
                    disabled={Number(item.current_stock) <= 0}
                    className="text-xs font-medium text-amber hover:text-amber-600 disabled:opacity-30 transition-colors"
                  >
                    Open New Bottle
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {inventory.length === 0 && (
          <div className="p-8 text-center text-gray-500">No bar inventory found.</div>
        )}
      </div>

      {showOpenModal && (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
            <h2 className="text-lg font-bold text-charcoal mb-4">Open New Bottle</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Volume (ml)</label>
                <input
                  type="number"
                  value={volume}
                  onChange={e => setVolume(e.target.value)}
                  placeholder="e.g. 750"
                  className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber focus:border-amber outline-none"
                />
              </div>
              <div className="flex gap-3 pt-2">
                <button 
                  onClick={() => setShowOpenModal(false)} 
                  className="flex-1 py-2.5 border border-gray-200 text-gray-600 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button 
                  onClick={() => openBottleMutation.mutate()}
                  disabled={!volume || openBottleMutation.isPending}
                  className="flex-1 py-2.5 bg-amber text-charcoal font-bold rounded-lg hover:bg-amber/90 transition-colors disabled:opacity-50"
                >
                  Confirm Open
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
