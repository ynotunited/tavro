'use client';

import { useEffect, useState } from 'react';
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
  serve_notes: string | null;
  modifiers: Record<string, { name: string }> | null;
  created_at: string;
}

interface Order {
  id: number;
  table_id: number | null;
  order_number: string;
  status: string;
  sent_at: string;
  table?: { name: string };
  waiter?: { id: number; name: string } | null;
  items: OrderItem[];
}

export default function BarDisplaySystem() {
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
    queryKey: ['bar-tickets'],
    queryFn: async () => (await api.get('/bar/tickets')).data.data,
    refetchInterval: 10000,
  });

  // WebSocket Subscription
  useEffect(() => {
    if (!user?.branch_id || !echo) return;

    const channelName = `private-branch.${user.branch_id}.bar`;
    const channel = echo.private(channelName);
    
    channel.listen('BarTicketUpdated', () => {
      if (soundEnabled) {
        const audio = new Audio('/bell.mp3');
        audio.play().catch(err => console.warn('Audio play blocked:', err));
      }
      queryClient.invalidateQueries({ queryKey: ['bar-tickets'] });
    });

    return () => {
      echo?.leave(channelName);
    };
  }, [user?.branch_id, queryClient, soundEnabled]);

  // Mutations
  const updateItemStatusMutation = useMutation({
    mutationFn: async ({ itemId, status }: { itemId: number; status: string }) => 
      api.patch(`/bar/items/${itemId}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['bar-tickets'] }),
  });

  const updateOrderStatusMutation = useMutation({
    mutationFn: async ({ orderId, status }: { orderId: number; status: string }) => 
      api.patch(`/bar/orders/${orderId}/status`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['bar-tickets'] }),
  });

  const saveServeNoteMutation = useMutation({
    mutationFn: async ({ itemId, note }: { itemId: number; note: string }) =>
      api.patch(`/bar/items/${itemId}/serve-notes`, { serve_notes: note }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bar-tickets'] });
      queryClient.invalidateQueries({ queryKey: ['orders'] });
    },
  });

  // Helpers
  const getElapsedMinutes = (sentAt: string) => {
    const diffMs = currentTime.getTime() - new Date(sentAt).getTime();
    return Math.floor(diffMs / 60000);
  };

  const getUrgencyColor = (minutes: number) => {
    if (minutes >= 15) return 'bg-red-500 text-white'; // Drinks should be faster than food
    if (minutes >= 8) return 'bg-amber-400 text-charcoal';
    return 'bg-sky-500 text-white'; // Use sky blue for bar base instead of green
  };

  if (isLoading) {
    return <div className="min-h-screen bg-[#0F172A] text-white flex items-center justify-center">Loading Bar Display...</div>;
  }

  return (
    <div className="min-h-screen bg-[#0F172A] text-white flex flex-col p-4">
      {/* Header */}
      <div className="flex justify-between items-center mb-6 pb-4 border-b border-white/10">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-sky-400">BAR DISPLAY</h1>
          <p className="text-white/40">{orders.length} active drink tickets</p>
        </div>
        
        <div className="flex items-center gap-6">
          <button 
            onClick={() => setSoundEnabled(!soundEnabled)}
            className={`px-4 py-2 rounded font-medium transition-colors ${soundEnabled ? 'bg-sky-500 text-white' : 'bg-white/5 text-white/40 border border-white/10'}`}
          >
            {soundEnabled ? '🔊 Sound ON' : '🔇 Sound OFF'}
          </button>
          
          <div className="text-right">
            <p className="text-4xl font-mono font-bold tracking-tighter text-sky-400">
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

            const allReady = order.items.every(i => i.status === 'READY');
            const hasPreparing = order.items.some(i => i.status === 'PREPARING');

            return (
              <div key={order.id} className="w-[300px] bg-[#1E293B] rounded-xl flex flex-col overflow-hidden border border-white/10 shadow-xl shrink-0 h-full max-h-[80vh]">
                
                {/* Ticket Header */}
                <div className={`${urgencyHeader} px-4 py-3 flex justify-between items-start shrink-0`}>
                  <div>
                    <h2 className="text-xl font-bold tracking-tight text-white">{order.table?.name || 'Takeaway'}</h2>
                    <p className="font-mono text-sm opacity-90">{order.order_number}</p>
                    {order.waiter?.name && (
                      <p className="text-xs font-medium opacity-90 mt-0.5">🧑‍🍼 Served by {order.waiter.name}</p>
                    )}
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
                      className={`pb-3 border-b border-white/5 last:border-0 last:pb-0 ${
                        item.status === 'READY' ? 'opacity-40' : ''
                      }`}
                    >
                      <div className="flex justify-between items-start mb-1">
                        <div className="flex gap-3">
                          <span className="font-bold text-xl min-w-[1.5rem]">{item.quantity}</span>
                          <div>
                            <p className={`font-bold text-lg leading-tight ${item.status === 'READY' ? 'line-through text-white/40' : ''}`}>
                              {item.product_name}
                            </p>
                            {item.variant_name && (
                              <p className="text-sm text-sky-400">({item.variant_name})</p>
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
                            item.status === 'READY' ? 'bg-white/5 border-white/10 text-white/40' :
                            item.status === 'PREPARING' ? 'bg-sky-500/20 border-sky-500/50 text-sky-400' :
                            'bg-white/10 border-white/20 text-white'
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
                        <ServeNoteEditor
                          key={item.id}
                          initial={item.serve_notes}
                          onSave={(note) => saveServeNoteMutation.mutate({ itemId: item.id, note })}
                        />
                      </div>
                    </div>
                  ))}
                </div>

                {/* Ticket Footer Action */}
                <div className="p-4 bg-white/5 border-t border-white/5 shrink-0">
                  {allReady ? (
                    <p className="text-center text-white/40 font-bold py-3">ALL DRINKS READY ✓</p>
                  ) : (
                    <button
                      onClick={() => {
                        const status = hasPreparing ? 'READY' : 'PREPARING';
                        updateOrderStatusMutation.mutate({ orderId: order.id, status });
                      }}
                      className="w-full py-3 rounded font-bold tracking-wider text-[#0F172A] bg-sky-400 hover:bg-sky-300 transition-colors"
                    >
                      {hasPreparing ? 'MARK ALL READY' : 'START PREPARING'}
                    </button>
                  )}
                </div>

              </div>
            );
          })}

          {orders.length === 0 && (
            <div className="w-full h-full flex flex-col items-center justify-center text-white/20 pt-32">
              <span className="text-6xl mb-4">🍸</span>
              <p className="text-2xl font-bold">Bar is clear</p>
              <p>Waiting for incoming drink orders...</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function ServeNoteEditor({ initial, onSave }: { initial: string | null; onSave: (note: string) => void }) {
  const [value, setValue] = useState(initial ?? '');

  return (
    <input
      value={value}
      onChange={(e) => setValue(e.target.value)}
      onBlur={() => {
        if (value.trim() !== (initial ?? '')) onSave(value.trim());
      }}
      onKeyDown={(e) => {
        if (e.key === 'Enter') (e.target as HTMLInputElement).blur();
      }}
      placeholder="Serve note for the floor — e.g. 3 glasses, no ice…"
      className={`w-full text-xs px-2 py-1.5 mt-1 rounded outline-none transition-colors ${
        value
          ? 'bg-sky-500/15 border border-sky-500/40 text-sky-200 placeholder-sky-200/40'
          : 'bg-white/5 border border-white/10 text-white/70 placeholder-white/30'
      }`}
    />
  );
}
