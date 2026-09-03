'use client';

import { useEffect, useSyncExternalStore } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { useAuthStore } from '@/store/authStore';

const publicRoutes = ['/login', '/forgot-password', '/reset-password', '/register', '/invite'];

// Admin (dev-company) panel is NOT a tenant area: it authenticates against the
// dedicated backend 'admin' guard and manages its own gating. The tenant auth
// guard below must never redirect /login or /dashboard for these routes.
const adminPanelPath = '/' + (process.env.NEXT_PUBLIC_ADMIN_PANEL_PATH || 'control-room-9f2k');

function isAdminRoute(pathname: string): boolean {
  return pathname === adminPanelPath || pathname.startsWith(adminPanelPath + '/') || pathname === '/panel' || pathname.startsWith('/panel/');
}

const emptySubscribe = () => () => {};

export function AuthProvider({ children }: { children: React.ReactNode }) {
  // Hydration guard: false on the server, true on the client after hydration.
  // useSyncExternalStore replaces the setMounted-in-effect pattern the
  // react-hooks/set-state-in-effect rule now rejects.
  const mounted = useSyncExternalStore(emptySubscribe, () => true, () => false);
  const router = useRouter();
  const pathname = usePathname();
  const token = useAuthStore((state) => state.token);

  useEffect(() => {
    if (!mounted) return;
    if (isAdminRoute(pathname)) return;

    const isPublicRoute = publicRoutes.includes(pathname);

    if (!token && !isPublicRoute) {
      router.push('/login');
    } else if (token && isPublicRoute) {
      router.push('/dashboard');
    }
  }, [token, pathname, mounted, router]);

  // Don't render until mounted to avoid hydration mismatch
  if (!mounted) {
    return null;
  }

  return <>{children}</>;
}
