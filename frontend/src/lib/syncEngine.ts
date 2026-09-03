import { v4 as uuidv4 } from 'uuid';
import { db, SyncQueueItem } from './db';
import api from './axios';

// ─── Connectivity Detection ───────────────────────────────────────────────────

let _isOnline = typeof navigator !== 'undefined' ? navigator.onLine : true;
const listeners = new Set<(online: boolean) => void>();

export function isOnline(): boolean {
  return _isOnline;
}

export function subscribeToOnlineStatus(fn: (online: boolean) => void) {
  listeners.add(fn);
  return () => listeners.delete(fn);
}

if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    _isOnline = true;
    listeners.forEach(fn => fn(true));
    runSyncQueue(); // auto-drain on reconnect
  });
  window.addEventListener('offline', () => {
    _isOnline = false;
    listeners.forEach(fn => fn(false));
  });
}

// ─── Idempotency Key Generation ───────────────────────────────────────────────

export function generateIdempotencyKey(): string {
  return uuidv4();
}

// ─── Queue a Mutation ──────────────────────────────────────────────────────────

export async function enqueueMutation(
  method: string,
  url: string,
  data: object | null = null
): Promise<string> {
  const key = generateIdempotencyKey();
  await db.sync_queue.add({
    idempotency_key: key,
    method,
    url,
    data,
    created_at: Date.now(),
    attempts: 0,
    status: 'pending',
  });
  return key;
}

// ─── Get Queue Depth ──────────────────────────────────────────────────────────

export async function getQueueDepth(): Promise<number> {
  return db.sync_queue.where('status').equals('pending').count();
}

// ─── Sync Queue Runner ────────────────────────────────────────────────────────

let _syncing = false;

export async function runSyncQueue(): Promise<void> {
  if (_syncing || !_isOnline) return;
  _syncing = true;

  try {
    const items = await db.sync_queue
      .where('status')
      .equals('pending')
      .sortBy('created_at');

    for (const item of items) {
      await processSyncItem(item);
    }
  } finally {
    _syncing = false;
  }
}

async function processSyncItem(item: SyncQueueItem): Promise<void> {
  const MAX_ATTEMPTS = 5;
  const backoffMs = Math.min(500 * Math.pow(2, item.attempts), 30_000);

  if (item.attempts >= MAX_ATTEMPTS) {
    await db.sync_queue.update(item.id!, { status: 'failed', error: 'Max retries exceeded' });
    notifyManager(`Sync failed permanently for ${item.method} ${item.url}. Manual intervention required.`);
    return;
  }

  try {
    await api.request({
      method: item.method as 'get' | 'post' | 'put' | 'patch' | 'delete',
      url: item.url,
      data: item.data,
      headers: { 'X-Idempotency-Key': item.idempotency_key },
    });

    // Success — remove from queue
    await db.sync_queue.delete(item.id!);
  } catch (err: unknown) {
    const status = (err as { response?: { status?: number } })?.response?.status;

    if (status === 409) {
      // Conflict — pause and notify
      await db.sync_queue.update(item.id!, { status: 'conflict', error: 'Conflict detected' });
      notifyManager(`Sync conflict on ${item.method} ${item.url}. Manager review required.`);
      return;
    }

    if (status && status >= 400 && status < 500) {
      // Client error (e.g. 422 validation) — mark failed, don't retry
      await db.sync_queue.update(item.id!, {
        status: 'failed',
        error: `Client error: ${status}`,
      });
      return;
    }

    // Network / server error — back off and increment attempts
    await db.sync_queue.update(item.id!, { attempts: item.attempts + 1 });
    await sleep(backoffMs);
  }
}

function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function notifyManager(message: string): void {
  // In-app fallback — can be wired to a toast or notification system
  console.warn('[SyncEngine]', message);
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('tavro:sync-alert', { detail: { message } }));
  }
}
