'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuthStore } from '@/store/authStore';
import Logo from '@/components/Logo';
import { BranchSwitcher } from '@/components/layout/BranchSwitcher';
import dynamic from 'next/dynamic';
import NotificationBell from '@/components/notifications/NotificationBell';

const OfflineBanner = dynamic(() => import('@/components/OfflineBanner'), { ssr: false });

const navItems = [
  { label: 'Dashboard',   href: '/dashboard',          icon: '📊' },
  { label: 'POS',         href: '/pos',                icon: '🧾' },
  { label: 'Orders',      href: '/orders',             icon: '📋' },
  { label: 'Floor Plan',  href: '/floorplan',          icon: '🗺' },
  { label: 'Kitchen',     href: '/kitchen',            icon: '🍳' },
  { label: 'Bar',         href: '/bar',                icon: '🍸' },
  { label: 'Bar Inventory',href: '/bar-inventory',     icon: '📦' },
  { label: 'Menu',        href: '/menu',               icon: '📖' },
  { label: 'Inventory',   href: '/inventory',          icon: '🏪' },
  { label: 'Shifts',      href: '/shifts',             icon: '⏱' },
  { label: 'Reports',     href: '/reports',            icon: '📈' },
];

const settingsItems = [
  { label: 'Team',        href: '/settings/team',       icon: '👥' },
  { label: 'Branches',    href: '/settings/branches',   icon: '🏢' },
  { label: 'Billing',     href: '/settings/billing',    icon: '💳' },
  { label: 'Status Page', href: '/settings/status',     icon: '🟢' },
  { label: 'Audit Logs',  href: '/settings/audit',      icon: '🔐' },
  { label: 'Notifications',href: '/settings/notifications', icon: '🔔' },
  { label: 'Settings',    href: '/settings',            icon: '⚙️' },
];

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuthStore();
  const pathname = usePathname();
  const router = useRouter();

  const handleLogout = () => {
    logout();
    router.push('/login');
  };

  const isActive = (href: string) => pathname === href || pathname.startsWith(href + '/');

  const NavLink = ({ item }: { item: { label: string; href: string; icon: string } }) => (
    <Link
      href={item.href}
      className={`flex items-center gap-3 px-3 py-2 text-sm transition-colors ${
        isActive(item.href)
          ? 'bg-amber text-charcoal font-semibold'
          : 'text-gray-300 hover:bg-gray-800 hover:text-white'
      }`}
    >
      <span className="text-base leading-none">{item.icon}</span>
      <span>{item.label}</span>
    </Link>
  );

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-gray-50">
      <OfflineBanner />

      {/* ── Desktop Sidebar ─────────────────────────────────────────── */}
      <aside className="hidden md:flex md:flex-col w-60 bg-charcoal text-white shrink-0 min-h-screen">
        {/* Logo */}
        <div className="px-4 py-5 border-b border-gray-700">
          <Logo variant="white" width={110} />
        </div>

        {/* Branch Switcher */}
        <div className="px-4 py-3 border-b border-gray-700">
          <BranchSwitcher />
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-2 py-3 space-y-0.5">
          {navItems.map((item) => <NavLink key={item.href} item={item} />)}

          <div className="pt-4">
            <p className="px-3 text-xs text-gray-500 uppercase tracking-wider mb-1">Management</p>
            {settingsItems.map((item) => <NavLink key={item.href} item={item} />)}
          </div>
        </nav>

        {/* User footer */}
        <div className="px-4 py-3 border-t border-gray-700 flex justify-between items-center">
          <div className="truncate">
            <p className="text-sm font-medium truncate">{user?.first_name} {user?.last_name}</p>
            <p className="text-xs text-gray-400 truncate">{user?.email}</p>
            <button
              onClick={handleLogout}
              className="mt-1 text-xs text-gray-400 hover:text-white transition-colors"
            >
              Sign Out
            </button>
          </div>
          <NotificationBell />
        </div>
      </aside>

      {/* ── Mobile Top Bar ────────────────────────────────────────────── */}
      <div className="md:hidden bg-charcoal text-white px-4 py-3 flex items-center justify-between">
        <Logo variant="white" width={100} />
        <div className="flex items-center gap-3">
          <NotificationBell />
          <div className="w-32">
            <BranchSwitcher />
          </div>
        </div>
      </div>

      {/* ── Mobile Bottom Nav ───────────────────────────────────────── */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-charcoal border-t border-gray-700 flex justify-around py-2">
        {navItems.slice(0, 5).map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className={`flex flex-col items-center gap-0.5 px-2 py-1 text-xs transition-colors ${
              isActive(item.href) ? 'text-amber' : 'text-gray-400'
            }`}
          >
            <span className="text-lg leading-none">{item.icon}</span>
            <span>{item.label}</span>
          </Link>
        ))}
      </nav>

      {/* ── Main Content ─────────────────────────────────────────────── */}
      <main className="tenant-content flex-1 p-4 md:p-8 pb-24 md:pb-8 overflow-auto">
        {children}
      </main>

    </div>
  );
}
