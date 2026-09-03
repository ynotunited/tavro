'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/axios';

interface Notification {
  id: string;
  type: string;
  data: {
    message: string;
    type: 'info' | 'warning' | 'error' | 'success';
    metadata?: Record<string, unknown>;
  };
  read_at: string | null;
  created_at: string;
}

export default function NotificationDrawer({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);

  const loadNotifications = async () => {
    setLoading(true);
    try {
      const res = await api.get('/notifications');
      setNotifications(res.data.data);
    } catch (err) {
      console.error('Failed to load notifications', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      // Async fetch; setState happens after await, not synchronously.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      void loadNotifications();
    }
  }, [isOpen]);

  const markRead = async (id: string) => {
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
    try {
      await api.post(`/notifications/${id}/read`);
    } catch (err) {
      console.error(err);
    }
  };

  const markAllRead = async () => {
    setNotifications(prev => prev.map(n => ({ ...n, read_at: new Date().toISOString() })));
    try {
      await api.post('/notifications/read-all');
    } catch (err) {
      console.error(err);
    }
  };

  if (!isOpen) return null;

  return (
    <>
      <div className="fixed inset-0 bg-black/20 z-40" onClick={onClose} />
      <div className="fixed right-0 top-0 bottom-0 w-80 md:w-96 bg-white shadow-2xl z-50 flex flex-col animate-slide-in-right">
        
        <div className="flex items-center justify-between p-4 border-b border-gray-100">
          <h2 className="font-bold text-lg text-charcoal">Notifications</h2>
          <div className="flex items-center gap-4">
            <button onClick={markAllRead} className="text-xs text-amber-600 font-semibold hover:underline">
              Mark all read
            </button>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
          {loading ? (
            <p className="text-sm text-center text-gray-400 mt-10">Loading...</p>
          ) : notifications.length === 0 ? (
            <div className="text-center mt-20">
              <span className="text-4xl">📭</span>
              <p className="text-gray-400 text-sm mt-4 font-medium">All caught up!</p>
            </div>
          ) : (
            notifications.map((n) => {
              const isUnread = !n.read_at;
              const typeIcons: Record<string, string> = { warning: '⚠️', error: '🚨', success: '✅', info: 'ℹ️' };
              const icon = typeIcons[n.data.type] || 'ℹ️';

              return (
                <div 
                  key={n.id}
                  onClick={() => { if (isUnread) markRead(n.id); }}
                  className={`p-4 rounded-xl border transition-all cursor-pointer ${
                    isUnread ? 'bg-white border-amber-200 shadow-sm' : 'bg-gray-100 border-gray-200 opacity-60'
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <span className="text-xl leading-none">{icon}</span>
                    <div>
                      <p className={`text-sm ${isUnread ? 'font-semibold text-charcoal' : 'text-gray-600'}`}>
                        {n.data.message}
                      </p>
                      <span className="text-[10px] text-gray-400 font-mono mt-1 block">
                        {new Date(n.created_at).toLocaleString()}
                      </span>
                    </div>
                    {isUnread && <span className="w-2 h-2 bg-amber-500 rounded-full mt-1 shrink-0 ml-auto" />}
                  </div>
                </div>
              );
            })
          )}
        </div>
      </div>
    </>
  );
}
