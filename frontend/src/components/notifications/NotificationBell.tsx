'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/axios';
import NotificationDrawer from './NotificationDrawer';

export default function NotificationBell() {
  const [unreadCount, setUnreadCount] = useState(0);
  const [isDrawerOpen, setDrawerOpen] = useState(false);

  const fetchUnread = async () => {
    try {
      const res = await api.get('/notifications/unread-count');
      setUnreadCount(res.data.count);
    } catch (err) {
      // fail silently
    }
  };

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchUnread();
    const interval = setInterval(fetchUnread, 30000); // poll every 30s as fallback
    return () => clearInterval(interval);
  }, []);

  return (
    <>
      <button 
        onClick={() => {
          setDrawerOpen(true);
          // optimistically clear count when opened
          setUnreadCount(0);
        }}
        className="relative p-2 text-gray-300 hover:text-white transition-colors"
      >
        <span className="text-xl">🔔</span>
        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-charcoal">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      <NotificationDrawer 
        isOpen={isDrawerOpen} 
        onClose={() => {
          setDrawerOpen(false);
          fetchUnread(); // refetch unread count when drawer closes
        }} 
      />
    </>
  );
}
