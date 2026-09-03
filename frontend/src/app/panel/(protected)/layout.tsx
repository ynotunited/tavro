'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import adminApi, { adminHref } from '@/lib/adminApi';
import Logo from '@/components/Logo';

const navItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Audit Logs', href: '/audit-logs' },
];

export default function AdminPanelLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [checking, setChecking] = useState(true);
  const [admin, setAdmin] = useState<{ name: string; email: string } | null>(null);

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const res = await adminApi.get('/me');
        if (alive) {
          setAdmin(res.data?.data ?? null);
          setChecking(false);
        }
      } catch {
        // Interceptor redirects to admin login on 401.
        if (alive) setChecking(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, []);

  if (checking) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 text-charcoal">
        <p className="text-sm text-muted">Checking session…</p>
      </div>
    );
  }

  const handleLogout = async () => {
    try {
      await adminApi.post('/logout');
    } catch {
      // Ignore — we redirect regardless.
    }
    router.push(adminHref('/login'));
    router.refresh();
  };

  return (
    <div className="min-h-screen flex bg-gray-50 text-charcoal">
      {/* Sidebar */}
      <aside className="w-60 bg-charcoal text-white shrink-0 min-h-screen flex flex-col">
        <div className="px-4 py-5 border-b border-gray-700">
          <Logo variant="white" width={96} />
        </div>

        <nav className="px-2 py-3 space-y-0.5">
          {navItems.map((item) => {
            const href = adminHref(item.href);
            const active = pathname === href;
            return (
              <Link
                key={item.href}
                href={href}
                className={`flex items-center gap-3 px-3 py-2 text-sm transition-colors ${
                  active ? 'bg-amber text-charcoal font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="px-4 py-3 border-t border-gray-700 mt-auto">
          <p className="text-sm font-medium truncate">{admin?.name || 'Admin'}</p>
          <p className="text-xs text-gray-400 truncate">{admin?.email}</p>
          <button
            onClick={handleLogout}
            className="mt-2 text-xs text-gray-400 hover:text-white transition-colors"
          >
            Sign Out
          </button>
        </div>
      </aside>

      {/* Content */}
      <main className="admin-content flex-1 p-6 md:p-8 overflow-auto">{children}</main>
    </div>
  );
}
