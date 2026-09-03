'use client';

import { useState } from 'react';
import dynamic from 'next/dynamic';
import { useAuthStore } from '@/store/authStore';

import { SetupChecklist } from '@/components/onboarding/SetupChecklist';

const OwnerDashboard = dynamic(() => import('@/components/dashboard/OwnerDashboard'), { ssr: false });
const ManagerDashboard = dynamic(() => import('@/components/dashboard/ManagerDashboard'), { ssr: false });

const OWNER_ROLES = ['admin', 'owner'];
const MANAGER_ROLES = ['manager', 'supervisor'];

export default function DashboardPage() {
  const user = useAuthStore(s => s.user);
  const userRole = user?.role ?? 'staff';

  const canSeeOwner = OWNER_ROLES.includes(userRole) || MANAGER_ROLES.includes(userRole);
  const defaultView = OWNER_ROLES.includes(userRole) ? 'owner' : 'manager';
  const [view, setView] = useState<'owner' | 'manager'>(defaultView);

  return (
    <div className="space-y-6">
      <SetupChecklist />
      {/* View Toggle (only show for roles that can see both) */}
      {canSeeOwner && (
        <div className="flex items-center gap-1 bg-gray-100 p-1 rounded-xl w-fit">
          <button
            onClick={() => setView('owner')}
            className={`px-5 py-2 rounded-lg text-sm font-semibold transition-all ${
              view === 'owner'
                ? 'bg-white text-charcoal shadow-sm'
                : 'text-gray-500 hover:text-charcoal'
            }`}
          >
            📊 Owner
          </button>
          <button
            onClick={() => setView('manager')}
            className={`px-5 py-2 rounded-lg text-sm font-semibold transition-all ${
              view === 'manager'
                ? 'bg-white text-charcoal shadow-sm'
                : 'text-gray-500 hover:text-charcoal'
            }`}
          >
            🏪 Operations
          </button>
        </div>
      )}

      {/* Dashboard Views */}
      {view === 'owner' && canSeeOwner && <OwnerDashboard />}
      {view === 'manager' && <ManagerDashboard />}
      {!canSeeOwner && <ManagerDashboard />}
    </div>
  );
}
