'use client';

import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';

interface ManagerData {
  active_orders: number;
  tables: { occupied: number; total: number };
  active_shifts: { id: number; status: string; user: { name: string } }[];
  pending_variances: { id: number; status: string; cash_variance: string; user: { name: string } }[];
  low_stock: { id: number; name: string; current_stock: string; min_level: string; unit_of_measure: string }[];
}

export default function ManagerDashboard() {
  const { data: mData, isLoading } = useQuery<ManagerData>({
    queryKey: ['dashboard', 'manager'],
    queryFn: async () => (await api.get('/dashboard/manager')).data,
  });

  if (isLoading) return <div className="p-8 text-center text-gray-500">Loading Operations...</div>;
  if (!mData) return null;

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Operations Dashboard</h1>
        <p className="text-sm text-gray-500">Live floor and inventory status</p>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
          <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Active Orders</p>
          <p className="text-3xl font-mono font-bold text-charcoal mt-2">{mData.active_orders}</p>
        </div>
        <div className="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
          <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Tables Occupied</p>
          <p className="text-3xl font-mono font-bold text-charcoal mt-2">
            {mData.tables.occupied} <span className="text-lg text-gray-300">/ {mData.tables.total}</span>
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Shifts & Variances */}
        <div className="space-y-6">
          <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-charcoal mb-4 flex justify-between">
              <span>Staff on Shift</span>
              <span className="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{mData.active_shifts.length}</span>
            </h3>
            <div className="space-y-3">
              {mData.active_shifts.map(s => (
                <div key={s.id} className="flex justify-between items-center text-sm border-b border-gray-50 pb-2 last:border-0">
                  <span className="text-gray-600 font-medium">{s.user.name}</span>
                  <span className={`text-[10px] uppercase font-bold px-2 py-0.5 rounded ${
                    s.status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                  }`}>
                    {s.status}
                  </span>
                </div>
              ))}
              {mData.active_shifts.length === 0 && <p className="text-gray-400 text-sm italic">No open shifts.</p>}
            </div>
          </div>

          {mData.pending_variances.length > 0 && (
            <div className="bg-white border border-amber-200 rounded-xl p-6 shadow-sm">
              <h3 className="text-sm font-semibold text-charcoal mb-4 flex items-center gap-2">
                <span className="text-amber-500">⚠️</span> Pending Variances
              </h3>
              <div className="space-y-3">
                {mData.pending_variances.map(v => (
                  <div key={v.id} className="flex justify-between items-center text-sm bg-amber-50 p-3 rounded-lg border border-amber-100">
                    <div>
                      <span className="text-charcoal font-medium block">{v.user.name}</span>
                      <span className="text-xs text-amber-700">Shift #{v.id}</span>
                    </div>
                    <div className="text-right">
                      <span className={`font-mono font-bold block ${Number(v.cash_variance) < 0 ? 'text-red-500' : 'text-emerald-500'}`}>
                        {Number(v.cash_variance) < 0 ? 'Short' : 'Over'}: ₦{Math.abs(Number(v.cash_variance)).toLocaleString()}
                      </span>
                      <a href="/shifts" className="text-xs font-semibold text-amber-600 hover:underline">Review →</a>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Low Stock Alerts */}
        <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
          <h3 className="text-sm font-semibold text-charcoal mb-4 flex justify-between items-center">
            <span>Low Stock Alerts</span>
            {mData.low_stock.length > 0 && <span className="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">{mData.low_stock.length}</span>}
          </h3>
          <div className="space-y-3">
            {mData.low_stock.map(item => {
              const current = Number(item.current_stock);
              const min = Number(item.min_level);
              const isOut = current <= 0;
              return (
                <div key={item.id} className="flex justify-between items-center text-sm border-b border-gray-50 pb-2 last:border-0">
                  <div className="pr-4">
                    <span className="font-semibold text-charcoal block">{item.name}</span>
                    <span className="text-xs text-gray-400">Min: {min} {item.unit_of_measure}</span>
                  </div>
                  <div className="text-right shrink-0">
                    <span className={`font-mono font-bold text-lg block ${isOut ? 'text-red-500' : 'text-amber-500'}`}>
                      {current.toFixed(1)} <span className="text-xs font-normal text-gray-400">{item.unit_of_measure}</span>
                    </span>
                    <span className={`text-[10px] uppercase font-bold px-1.5 py-0.5 rounded ${isOut ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'}`}>
                      {isOut ? 'Out of Stock' : 'Low'}
                    </span>
                  </div>
                </div>
              );
            })}
            {mData.low_stock.length === 0 && <p className="text-gray-400 text-sm italic">Inventory levels are healthy.</p>}
          </div>
        </div>
      </div>
    </div>
  );
}
