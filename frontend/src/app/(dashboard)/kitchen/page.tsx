'use client';

import { useEffect, useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { useAuthStore } from '@/store/authStore';
import { echo } from '@/lib/echo';

interface OrderItem {
  id: number;
  order_id: number;
  product_name: string;
  variant_name: string | null;
  quantity: number;
  status: string;
  notes: string | null;
  modifiers: Record<string, { name: string }>;
  created_at: string;
}

interface Order {
  id: number;
  table_id: number | null;
  order_number: string;
  status: string;
  sent_at: string;
  table?: { name: string };
  items: OrderItem[];
}

export default function KitchenDisplaySystem() {
  const queryClient = useQueryClient();
  const { user } = useAuthStore();
  const [currentTime, setCurrentTime] = useState(new Date());
  const [soundEnabled, setSoundEnabled] = useState(false);

  // Live clock
  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  // Fetch Tickets
  const { data: orders = [], isLoading } = useQuery<Order[]>({
    queryKey: ['kitchen-tickets'],
    queryFn: async () => (await api.get('/kitchen/tickets')).data.data,
    refetchInterval: 10000, // Fallback polling
  });

  // WebSocket Subscription
  useEffect(() => {
    if (!user?.branch_id || !echo) return;

    const channelName = `private-branch.${user.branch_id}.kitchen`;
    const channel = echo.private(channelName);
    
    channel.listen('KitchenTicketUpdated', (_e: unknown) => {
      // Play sound if enabled
      if (soundEnabled) {
        const audio = new Audio('/bell.mp3'); // Assuming we have a bell.mp3 in public/
        audio.play().catch(err => console.warn('Audio play blocked:', err));
      }
      // Invalidate and refetch immediately
      queryClient.invalidateQueries({ queryKey: ['kitchen-tickets'] });
    });

    return () => {
      echo?.leave(channelName);
    };
  }, [user?.branch_id, queryClient, soundEnabled]);

  // Mutations
  const updateItemStatusMutation = useMutation({
    mutationFn: async ({ itemId, status }: { itemId: number; status: string }) => 
      api.patch(`/kitchen/items/${itemId}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['kitchen-tickets'] }),
  });

  const updateOrderStatusMutation = useMutation({
    mutationFn: async ({ orderId, status }: { orderId: number; status: string }) => 
      api.patch(`/kitchen/orders/${orderId}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['kitchen-tickets'] }),
  });

  // Helpers
  const getElapsedMinutes = (sentAt: string) => {
    const diffMs = currentTime.getTime() - new Date(sentAt).getTime();
    return Math.floor(diffMs / 60000);
  };

  const getUrgencyColor = (minutes: number) => {
    if (minutes >= 30) return 'bg-red-500 text-white';
    if (minutes >= 15) return 'bg-amber-400 text-charcoal';
    return 'bg-emerald-500 text-white';
  };

  if (isLoading) {
    return <div className="min-h-screen bg-charcoal text-white flex items-center justify-center">Loading Kitchen Display...</div>;
  }

  return (
    <div className="min-h-screen bg-charcoal text-white flex flex-col p-4">
      {/* Header */}
      <div className="flex justify-between items-center mb-6 pb-4 border-b border-gray-700">
        <div>
          <h1 className="text-3xl font-bold text-white">KITCHEN DISPLAY</h1>
          <p className="text-gray-400">{orders.length} active tickets</p>
        </div>
        
        <div className="flex items-center gap-6">
          <button 
            onClick={() => setSoundEnabled(!soundEnabled)}
            className={`px-4 py-2 rounded font-medium ${soundEnabled ? 'bg-amber-400 text-charcoal' : 'bg-gray-800 text-gray-400 border border-gray-600'}`}
          >
            {soundEnabled ? '🔊 Sound ON' : '🔇 Sound OFF'}
          </button>
          
          <div className="text-right">
            <p className="text-4xl font-mono font-bold tracking-tighter">
              {currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
            </p>
          </div>
        </div>
      </div>

      {/* Tickets Grid */}
      <div className="flex-1 overflow-x-auto pb-4">
        <div className="flex gap-4 h-full items-start" style={{ width: 'max-content' }}>
          {orders.map(order => {
            const elapsed = getElapsedMinutes(order.sent_at);
            const urgencyHeader = getUrgencyColor(elapsed);

            // Check if all items are ready
            const allReady = order.items.every(i => i.status === 'READY');
            const hasPreparing = order.items.some(i => i.status === 'PREPARING');

            return (
              <div key={order.id} className="w-[350px] bg-[#1E293B] rounded-xl flex flex-col overflow-hidden border border-gray-700 shadow-xl shrink-0 h-full max-h-[80vh]">
                
                {/* Ticket Header */}
                <div className={`${urgencyHeader} px-4 py-3 flex justify-between items-start shrink-0`}>
                  <div>
                    <h2 className="text-xl font-bold tracking-tight text-white">{order.table?.name || 'Takeaway'}</h2>
                    <p className="font-mono text-sm opacity-90">{order.order_number}</p>
                  </div>
                  <div className="text-right">
                    <span className="text-2xl font-bold font-mono">{elapsed}m</span>
                  </div>
                </div>

                {/* Items List */}
                <div className="flex-1 overflow-y-auto p-4 space-y-4">
                  {order.items.map(item => (
                    <div 
                      key={item.id} 
                      className={`pb-3 border-b border-gray-700/50 last:border-0 last:pb-0 ${
                        item.status === 'READY' ? 'opacity-40' : ''
                      }`}
                    >
                      <div className="flex justify-between items-start mb-1">
                        <div className="flex gap-3">
                          <span className="font-bold text-xl min-w-[1.5rem]">{item.quantity}</span>
                          <div>
                            <p className={`font-bold text-lg leading-tight ${item.status === 'READY' ? 'line-through text-gray-500' : ''}`}>
                              {item.product_name}
                            </p>
                            {item.variant_name && (
                              <p className="text-sm text-gray-400">({item.variant_name})</p>
                            )}
                          </div>
                        </div>
                        {/* Item Status Toggle */}
                        <button
                          onClick={() => {
                            const nextStatus = item.status === 'SENT' ? 'PREPARING' : item.status === 'PREPARING' ? 'READY' : 'SENT';
                            updateItemStatusMutation.mutate({ itemId: item.id, status: nextStatus });
                          }}
                          className={`px-3 py-1 rounded text-xs font-bold border transition-colors ${
                            item.status === 'READY' ? 'bg-gray-800 border-gray-600 text-gray-400' :
                            item.status === 'PREPARING' ? 'bg-amber-400/20 border-amber-400/50 text-amber-400' :
                            'bg-blue-500/20 border-blue-500/50 text-blue-400'
                          }`}
                        >
                          {item.status}
                        </button>
                      </div>

                      {/* Notes & Modifiers */}
                      <div className="pl-9 space-y-1 mt-2">
                        {item.modifiers && Object.entries(item.modifiers).map(([group, mod]) => (
                          <p key={group} className="text-sm text-amber-300">
                            + {mod.name}
                          </p>
                        ))}
                        {item.notes && (
                          <p className="text-sm font-medium text-red-400 bg-red-400/10 p-2 rounded inline-block">
                            📝 {item.notes}
                          </p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Ticket Footer Action */}
                <div className="p-4 bg-gray-800 border-t border-gray-700 shrink-0">
                  {allReady ? (
                    <p className="text-center text-gray-400 font-bold py-3">ALL ITEMS READY ✓</p>
                  ) : (
                    <button
                      onClick={() => {
                        const status = hasPreparing ? 'READY' : 'PREPARING';
                        updateOrderStatusMutation.mutate({ orderId: order.id, status });
                      }}
                      className="w-full py-3 rounded font-bold tracking-wider text-[#0F172A] bg-amber-400 hover:bg-amber-300 transition-colors"
                    >
                      {hasPreparing ? 'MARK ALL READY' : 'START PREPARING'}
                    </button>
                  )}
                </div>

              </div>
            );
          })}

          {orders.length === 0 && (
            <div className="w-full h-full flex flex-col items-center justify-center text-gray-500 pt-32">
              <span className="text-6xl mb-4">🍳</span>
              <p className="text-2xl font-bold">Kitchen is clear</p>
              <p>Waiting for incoming orders...</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
