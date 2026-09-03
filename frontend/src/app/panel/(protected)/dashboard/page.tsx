'use client';

import { useEffect, useState } from 'react';
import adminApi from '@/lib/adminApi';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Skeleton } from '@/components/ui/Skeleton';

interface DashboardData {
  generated_at: string;
  platform: { organizations: number; users: number; unverified_users: number };
  traffic_24h: { requests: number; errors: number; error_rate: number };
  support: { open_issues: number; urgent_issues: number };
  admins: number;
}

function StatCard({ label, value, accent = false }: { label: string; value: string | number; accent?: boolean }) {
  return (
    <Card className={accent ? 'border-amber-400 bg-amber-50' : ''}>
      <CardHeader>
        <CardTitle className="text-xs uppercase tracking-wider text-muted">{label}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="text-3xl font-bold text-primary">{value}</p>
      </CardContent>
    </Card>
  );
}

export default function AdminDashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const res = await adminApi.get('/dashboard');
        if (alive) setData(res.data?.data ?? null);
      } catch {
        if (alive) setError('Could not load dashboard data.');
      }
    })();
    return () => {
      alive = false;
    };
  }, []);

  if (!data) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-primary">Platform Overview</h1>
          <p className="text-sm text-muted">Dev-company monitoring dashboard</p>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {Array.from({ length: 8 }).map((_, i) => (
            <Skeleton key={i} className="h-28 w-full" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-primary">Platform Overview</h1>
          <p className="text-sm text-muted">
            Snapshot as of {new Date(data.generated_at).toLocaleString()}
          </p>
        </div>
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Organizations" value={data.platform.organizations} />
        <StatCard label="Users" value={data.platform.users} />
        <StatCard label="Unverified Users" value={data.platform.unverified_users} />
        <StatCard label="Admins" value={data.admins} />
        <StatCard label="Requests (24h)" value={data.traffic_24h.requests} />
        <StatCard label="Errors (24h)" value={data.traffic_24h.errors} accent={data.traffic_24h.errors > 0} />
        <StatCard label="Error Rate (24h)" value={`${data.traffic_24h.error_rate}%`} />
        <StatCard label="Open Issues" value={data.support.open_issues} accent={data.support.open_issues > 0} />
      </div>
    </div>
  );
}
