import Axios from 'axios';

/**
 * Admin (dev-company) API client.
 *
 * DIFFERENT from the tenant `@/lib/axios` client: this talks to the admin
 * panel backend which uses the dedicated 'admin' session guard (cookies), NOT
 * the tenant HMAC/bearer scheme. It therefore must NOT do HMAC signing, must
 * NOT attach the tenant token, and must handle 401 by returning to the admin
 * login (not the customer /login).
 *
 * Requests are same-origin: /{ADMIN_PANEL_PATH}/api/* — rewritten by
 * next.config to http://localhost:8000/{ADMIN_PANEL_PATH}/* so the admin
 * session cookie (SameSite=lax) is forwarded correctly.
 */

const PANEL = (process.env.NEXT_PUBLIC_ADMIN_PANEL_PATH || 'control-room-9f2k').replace(/^\/+|\/+$/g, '');

const adminApi = Axios.create({
  baseURL: `/${PANEL}/api`,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

adminApi.interceptors.response.use(
  (response) => response,
  (error) => {
    // Admin session expired / not authenticated.
    if (error?.response?.status === 401 && typeof window !== 'undefined') {
      // Full-page navigation (not SPA route push) so a stale expired-session
      // page is fully discarded and the admin login renders fresh. This is an
      // axios interceptor (not a component), so useRouter()/redirect() aren't
      // available — location assignment is the correct mechanism here.
      // eslint-disable-next-line @next/next/no-location-assign-relative-destination
      window.location.href = `/${PANEL}/login`;
    }
    return Promise.reject(error);
  }
);

export default adminApi;

/** Builds a client-side href to a page inside the admin panel. */
export function adminHref(path: string): string {
  return `/${PANEL}${path.startsWith('/') ? path : `/${path}`}`;
}
