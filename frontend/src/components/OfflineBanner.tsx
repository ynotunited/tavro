'use client';

import { useOnlineStatus } from '@/hooks/useOnlineStatus';

export default function OfflineBanner() {
  const { isOnline, queueDepth, syncing } = useOnlineStatus();

  if (isOnline && queueDepth === 0) return null;

  return (
    <div
      className={`fixed top-0 left-0 right-0 z-[9999] flex items-center justify-between px-4 py-2 text-sm font-semibold transition-all ${
        !isOnline
          ? 'bg-amber-500 text-amber-950'
          : 'bg-blue-500 text-white'
      }`}
    >
      <div className="flex items-center gap-2">
        {!isOnline ? (
          <>
            <span className="text-base">📡</span>
            <span>Offline mode — changes are saved locally</span>
          </>
        ) : syncing ? (
          <>
            <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            <span>Syncing {queueDepth} pending transaction{queueDepth !== 1 ? 's' : ''}...</span>
          </>
        ) : (
          <>
            <span className="text-base">⏳</span>
            <span>{queueDepth} transaction{queueDepth !== 1 ? 's' : ''} queued for sync</span>
          </>
        )}
      </div>

      {queueDepth > 0 && (
        <span className="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
          {queueDepth} queued
        </span>
      )}
    </div>
  );
}
