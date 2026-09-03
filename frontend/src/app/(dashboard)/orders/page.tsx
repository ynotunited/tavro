'use client';

import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { Card, CardContent } from '@/components/ui/Card';

interface Order {
  id: number;
  order_number: string;
  status: string;
  total_amount: string;
  opened_at: string;
  table?: { name: string };
  waiter?: { first_name: string; last_name: string };
  items?: unknown[];
}

export default function OrdersPage() {
  const { data: orders = [], isLoading } = useQuery<Order[]>({
    queryKey: ['orders'],
    queryFn: async () => (await api.get('/orders')).data.data,
  });

  if (isLoading) {
    return <div className="p-8">Loading orders...</div>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Orders History</h1>
        <p className="text-sm text-gray-500">View and manage all active and historical orders.</p>
      </div>

      <div className="hidden md:block bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table className="w-full text-sm text-left">
          <thead className="bg-gray-50 border-b border-gray-200 text-charcoal font-semibold">
            <tr>
              <th className="px-6 py-4">Order #</th>
              <th className="px-6 py-4">Table</th>
              <th className="px-6 py-4">Status</th>
              <th className="px-6 py-4">Total</th>
              <th className="px-6 py-4">Waiter</th>
              <th className="px-6 py-4">Time</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {orders.map((order) => (
              <tr key={order.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 font-mono font-medium">{order.order_number}</td>
                <td className="px-6 py-4">{order.table?.name || 'Takeaway'}</td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    order.status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' :
                    order.status === 'SENT' ? 'bg-amber-100 text-amber-700' :
                    order.status === 'CLOSED' ? 'bg-gray-100 text-gray-700' :
                    'bg-sky-100 text-sky-700'
                  }`}>
                    {order.status}
                  </span>
                </td>
                <td className="px-6 py-4 font-mono font-medium">₦{Number(order.total_amount).toLocaleString()}</td>
                <td className="px-6 py-4">{order.waiter?.first_name} {order.waiter?.last_name}</td>
                <td className="px-6 py-4 text-gray-500">{new Date(order.opened_at).toLocaleTimeString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {orders.length === 0 && (
          <div className="p-8 text-center text-gray-500">No orders found.</div>
        )}
      </div>

      <div className="md:hidden space-y-4">
        {orders.map((order) => (
          <Card key={order.id}>
            <CardContent className="p-4 flex flex-col gap-2">
              <div className="flex justify-between items-center">
                <span className="font-mono font-bold">{order.order_number}</span>
                <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                  order.status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' :
                  order.status === 'SENT' ? 'bg-amber-100 text-amber-700' :
                  order.status === 'CLOSED' ? 'bg-gray-100 text-gray-700' :
                  'bg-sky-100 text-sky-700'
                }`}>
                  {order.status}
                </span>
              </div>
              <div className="flex justify-between text-sm text-gray-600">
                <span>{order.table?.name || 'Takeaway'}</span>
                <span>{order.waiter?.first_name}</span>
              </div>
              <div className="flex justify-between items-center pt-2 border-t border-gray-100 mt-1">
                <span className="text-xs text-gray-400">{new Date(order.opened_at).toLocaleTimeString()}</span>
                <span className="font-mono font-bold text-amber">₦{Number(order.total_amount).toLocaleString()}</span>
              </div>
            </CardContent>
          </Card>
        ))}
        {orders.length === 0 && (
          <div className="p-8 text-center text-gray-500">No orders found.</div>
        )}
      </div>
    </div>
  );
}
