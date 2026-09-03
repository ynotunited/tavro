'use client';

import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';

interface Alert {
  severity: 'high' | 'medium' | 'low';
  message: string;
}

interface OwnerData {
  hero: {
    gross_sales: number;
    net_sales: number;
    orders: number;
    aov: number;
  };
  sparkline: { date: string; value: number }[];
  payment_mix: { name: string; value: number }[];
  top_products: { product_name: string; qty: number; revenue: number }[];
}

import { LineChart, Line, XAxis, Tooltip, ResponsiveContainer } from 'recharts';

export default function OwnerDashboard() {
  const { data: ownerData, isLoading: isLoadingOwner } = useQuery<OwnerData>({
    queryKey: ['dashboard', 'owner'],
    queryFn: async () => (await api.get('/dashboard/owner')).data,
  });

  const { data: alerts = [], isLoading: isLoadingAlerts } = useQuery<Alert[]>({
    queryKey: ['dashboard', 'alerts'],
    queryFn: async () => (await api.get('/dashboard/alerts')).data.data,
  });

  if (isLoadingOwner || isLoadingAlerts) {
    return <div className="p-8 text-center text-gray-500">Loading Dashboard...</div>;
  }

  if (!ownerData) return null;

  return (
    <div className="space-y-6 max-w-5xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Owner Dashboard</h1>
        <p className="text-sm text-gray-500">Business performance at a glance (Today)</p>
      </div>

      {/* Hero Metrics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { label: 'Gross Sales', val: `₦${ownerData.hero.gross_sales.toLocaleString()}` },
          { label: 'Net Sales', val: `₦${ownerData.hero.net_sales.toLocaleString()}` },
          { label: 'Orders', val: ownerData.hero.orders.toLocaleString() },
          { label: 'Avg Order Val', val: `₦${ownerData.hero.aov.toLocaleString()}` },
        ].map(m => (
          <div key={m.label} className="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
            <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">{m.label}</p>
            <p className="text-2xl lg:text-3xl font-mono font-bold text-charcoal mt-2">{m.val}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Col: Sparkline & BI Alerts */}
        <div className="lg:col-span-2 space-y-6">
          {/* Revenue Sparkline (7 Day) */}
          <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-charcoal mb-4">7-Day Net Revenue Trend</h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={ownerData.sparkline}>
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#9CA3AF' }} dy={10} />
                  <Tooltip 
                    formatter={(value) => [`₦${Number(value ?? 0).toLocaleString()}`, 'Net Revenue']}
                    contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                  />
                  <Line type="monotone" dataKey="value" stroke="#F59E0B" strokeWidth={3} dot={{ r: 4, fill: '#F59E0B', strokeWidth: 0 }} activeDot={{ r: 6 }} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Business Intelligence Alerts */}
          {alerts.length > 0 && (
            <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
              <h3 className="text-sm font-semibold text-charcoal mb-4">Actionable Alerts</h3>
              <div className="space-y-3">
                {alerts.map((alert, i) => (
                  <div key={i} className={`p-4 rounded-lg flex items-start gap-3 ${
                    alert.severity === 'high' ? 'bg-red-50 text-red-700 border border-red-100' :
                    alert.severity === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-100' :
                    'bg-blue-50 text-blue-700 border border-blue-100'
                  }`}>
                    <span className="text-lg leading-none">{alert.severity === 'high' ? '🚨' : alert.severity === 'medium' ? '⚠️' : 'ℹ️'}</span>
                    <p className="text-sm font-medium">{alert.message}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Right Col: Breakdowns */}
        <div className="space-y-6">
          {/* Payment Mix */}
          <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-charcoal mb-4">Payment Mix</h3>
            <div className="space-y-3">
              {ownerData.payment_mix.map(p => (
                <div key={p.name} className="flex justify-between items-center text-sm border-b border-gray-50 pb-2 last:border-0">
                  <span className="text-gray-600 font-medium">{p.name}</span>
                  <span className="font-mono font-bold text-charcoal">₦{p.value.toLocaleString()}</span>
                </div>
              ))}
              {ownerData.payment_mix.length === 0 && <p className="text-gray-400 text-sm italic">No payments today.</p>}
            </div>
          </div>

          {/* Top Products */}
          <div className="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 className="text-sm font-semibold text-charcoal mb-4">Top 5 Products</h3>
            <div className="space-y-3">
              {ownerData.top_products.map(p => (
                <div key={p.product_name} className="flex justify-between items-center text-sm border-b border-gray-50 pb-2 last:border-0">
                  <div className="truncate pr-4 flex-1">
                    <span className="font-semibold text-charcoal">{p.product_name}</span>
                  </div>
                  <div className="text-right shrink-0">
                    <span className="font-mono text-xs text-gray-400 mr-2">{p.qty}x</span>
                    <span className="font-mono font-bold text-charcoal">₦{Number(p.revenue).toLocaleString()}</span>
                  </div>
                </div>
              ))}
              {ownerData.top_products.length === 0 && <p className="text-gray-400 text-sm italic">No products sold today.</p>}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
