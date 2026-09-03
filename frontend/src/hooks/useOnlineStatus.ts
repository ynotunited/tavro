'use client';

import { useState, useEffect } from 'react';
import { isOnline, subscribeToOnlineStatus, getQueueDepth, runSyncQueue } from '@/lib/syncEngine';

export function useOnlineStatus() {
  const [online, setOnline] = useState(() => typeof window !== 'undefined' ? isOnline() : true);
  const [queueDepth, setQueueDepth] = useState(0);
  const [syncing, setSyncing] = useState(false);

  useEffect(() => {
    // Subscribe to connectivity changes
    const unsubscribe = subscribeToOnlineStatus(async (isNowOnline) => {
      setOnline(isNowOnline);
      if (isNowOnline) {
        setSyncing(true);
        await runSyncQueue();
        setSyncing(false);
        const depth = await getQueueDepth();
        setQueueDepth(depth);
      }
    });

    // Poll queue depth every 3 seconds
    const interval = setInterval(async () => {
      const depth = await getQueueDepth();
      setQueueDepth(depth);
    }, 3000);

    // Listen for sync alerts (conflict / failure)
    const handleSyncAlert = (e: Event) => {
      const detail = (e as CustomEvent<{ message: string }>).detail;
      console.warn('[SyncAlert]', detail.message);
      // Could be wired to a toast here
    };
    window.addEventListener('tavro:sync-alert', handleSyncAlert);

    return () => {
      unsubscribe();
      clearInterval(interval);
      window.removeEventListener('tavro:sync-alert', handleSyncAlert);
    };
  }, []);

  return { isOnline: online, queueDepth, syncing };
}
