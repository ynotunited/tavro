import Dexie, { type Table } from 'dexie';

// ─── Schema Types ─────────────────────────────────────────────────────────────

export interface CachedProduct {
  id: number;
  name: string;
  price: string;
  category_id: number | null;
  category_name: string | null;
  modifiers: object[];
  track_inventory: boolean;
  cached_at: number; // timestamp ms
}

export interface CachedTable {
  id: number;
  name: string;
  status: string;
  capacity: number;
  section: string | null;
  cached_at: number;
}

export interface OfflineOrder {
  id: string;             // local UUID e.g. "offline_abc123"
  serverId?: number;      // set after successful sync
  table_id: number | null;
  table_name: string | null;
  cover_count: number;
  status: string;
  items: OfflineOrderItem[];
  payments: OfflinePayment[];
  subtotal: number;
  discount_amount: number;
  opened_at: string;
  synced: boolean;
}

export interface OfflineOrderItem {
  id: string;             // local UUID
  product_id: number;
  product_name: string;
  quantity: number;
  unit_price: number;
  subtotal: number;
  notes: string | null;
  modifiers: object[];
  voided_at: string | null;
}

export interface OfflinePayment {
  id: string;             // local UUID
  method: 'CASH';         // only cash allowed offline
  amount: number;
  created_at: string;
}

export interface SyncQueueItem {
  id?: number;            // auto-increment (Dexie PK)
  idempotency_key: string;
  method: string;         // POST, PATCH, PUT, DELETE
  url: string;
  data: object | null;
  created_at: number;
  attempts: number;
  status: 'pending' | 'failed' | 'conflict';
  error?: string;
}

export interface AppConfig {
  key: string;
  value: string | number | object;
}

// ─── Dexie Database ────────────────────────────────────────────────────────────
// NOTE: Do NOT name a property "tables" — Dexie uses that as a base-class array.

class TavroDB extends Dexie {
  products!: Table<CachedProduct>;
  venue_tables!: Table<CachedTable>;   // renamed to avoid conflict with Dexie.tables
  offline_orders!: Table<OfflineOrder>;
  sync_queue!: Table<SyncQueueItem>;
  config!: Table<AppConfig>;

  constructor() {
    super('tavro_pos');

    this.version(1).stores({
      products:       'id, category_id, cached_at',
      venue_tables:   'id, status, cached_at',
      offline_orders: 'id, synced, status',
      sync_queue:     '++id, status, created_at',
      config:         'key',
    });
  }
}

export const db = new TavroDB();

// ─── Cache TTLs ────────────────────────────────────────────────────────────────
export const TTL = {
  products: 60 * 60 * 1000,  // 1 hour
  tables:    5 * 60 * 1000,  // 5 minutes
};

export function isFresh(cached_at: number, ttl: number): boolean {
  return Date.now() - cached_at < ttl;
}
