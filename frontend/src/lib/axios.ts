import Axios, { type InternalAxiosRequestConfig } from 'axios';
import { v4 as uuidv4 } from 'uuid';

const MUTATION_METHODS = new Set(['post', 'put', 'patch', 'delete']);

const api = Axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true
});

// ─── HMAC request signing ─────────────────────────────────────────────────────
// Every mutating request is signed: METHOD\n{path}\n{sha256(body)}\n{ts}\n{nonce}
// Server: app/Http/Middleware/VerifyRequestSignature + app/Services/ApiSigner.

const enc = new TextEncoder();

async function sha256Hex(input: string): Promise<string> {
  const digest = await crypto.subtle.digest('SHA-256', enc.encode(input));
  return Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

async function hmacSha256Hex(secret: string, message: string): Promise<string> {
  const key = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign']
  );
  const sig = await crypto.subtle.sign('HMAC', key, enc.encode(message));
  return Array.from(new Uint8Array(sig))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

function canonicalBody(data: unknown): string {
  if (data === undefined || data === null) return '';
  if (typeof data === 'string') return data;
  // Matches axios' own JSON serialization for plain-object bodies.
  return JSON.stringify(data);
}

async function signRequest(config: InternalAxiosRequestConfig): Promise<void> {
  const method = (config.method ?? 'get').toUpperCase();
  if (!MUTATION_METHODS.has(method.toLowerCase())) return;

  const authStorage = localStorage.getItem('tavro-auth');
  if (!authStorage) return;

  let secret: string | null = null;
  try {
    secret = JSON.parse(authStorage)?.state?.signingSecret ?? null;
  } catch {
    return; // corrupted store — leave unsigned; auth layer will surface it
  }
  if (!secret) return; // login/register happen before a secret exists

  const url = config.url ?? '';
  const fullUrl = url.startsWith('http')
    ? url
    : `${config.baseURL ?? ''}${url.startsWith('/') ? url : `/${url}`}`;
  const path = new URL(fullUrl).pathname;

  const timestamp = Math.floor(Date.now() / 1000);
  const nonce = uuidv4();
  const bodyHash = await sha256Hex(canonicalBody(config.data));

  const canonical = [method, path, bodyHash, timestamp, nonce].join('\n');
  const signature = await hmacSha256Hex(secret, canonical);

  config.headers['X-Timestamp'] = String(timestamp);
  config.headers['X-Nonce'] = nonce;
  config.headers['X-Signature'] = signature;
}

// ─── Request Interceptor ──────────────────────────────────────────────────────
api.interceptors.request.use(async (config) => {
  // Attach auth token
  if (typeof window !== 'undefined') {
    const authStorage = localStorage.getItem('tavro-auth');
    if (authStorage) {
      try {
        const auth = JSON.parse(authStorage);
        if (auth?.state?.token) {
          config.headers.Authorization = `Bearer ${auth.state.token}`;
        }
      } catch {
        // Corrupted auth data — clear it
        localStorage.removeItem('tavro-auth');
      }
    }
  }

  // Sign every mutating request with the session HMAC secret
  if (typeof window !== 'undefined' && crypto?.subtle) {
    await signRequest(config);
  }

  // Attach idempotency key to all mutations (used by sync engine for safe replay)
  if (config.method && MUTATION_METHODS.has(config.method.toLowerCase())) {
    if (!config.headers['X-Idempotency-Key']) {
      config.headers['X-Idempotency-Key'] = uuidv4();
    }
  }

  // Offline interception — queue mutations instead of failing
  if (
    typeof window !== 'undefined' &&
    !navigator.onLine &&
    config.method &&
    MUTATION_METHODS.has(config.method.toLowerCase())
  ) {
    // Dynamically import to avoid circular dep at module load time
    const { enqueueMutation } = await import('./syncEngine');
    await enqueueMutation(
      config.method.toUpperCase(),
      config.url ?? '',
      (config.data ? (typeof config.data === 'string' ? JSON.parse(config.data) : config.data) : null) as object | null
    );

    // Return a synthetic "offline queued" response instead of throwing
    const offlineError = new Error('OFFLINE_QUEUED') as Error & { isOfflineQueued: boolean };
    offlineError.isOfflineQueued = true;
    return Promise.reject(offlineError);
  }

  return config;
});

// ─── Response Interceptor ─────────────────────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Swallow offline-queued errors gracefully (not a real network failure)
    if (error?.isOfflineQueued) {
      return Promise.reject(error);
    }

    if (error.response?.status === 401 || error.response?.status === 419) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem('tavro-auth');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;
