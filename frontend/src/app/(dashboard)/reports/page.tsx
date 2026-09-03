'use client';

import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import { Skeleton } from '@/components/ui/Skeleton';
import { EmptyState } from '@/components/ui/EmptyState';

interface SalesSummary {
  orders: number;
  gross_sales: number;
  discounts: number;
  net_sales: number;
  aov: number;
}

interface PaymentRow {
  method: string;
  total: number;
  count: number;
}

interface StaffRow {
  waiter_id: number | null;
  waiter?: { id: number; name: string } | null;
  orders: number;
  gross: number;
  discounts: number;
}

const METHOD_LABELS: Record<string, string> = {
  CASH: 'Cash',
  CARD: 'Card',
  TRANSFER: 'Bank Transfer',
  POS: 'POS',
  MOBILE: 'Mobile Money',
  CREDIT: 'Credit',
};

const PRESETS = [
  { label: 'Today', days: 0 },
  { label: '7D', days: 6 },
  { label: '30D', days: 29 },
];

const fmt = (n: number) => `₦${Number(n || 0).toLocaleString()}`;

const toDate = (d: Date) => {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
};

export default function ReportsPage() {
  const today = useMemo(() => new Date(), []);
  const [start, setStart] = useState<string>(toDate(today));
  const [end, setEnd] = useState<string>(toDate(today));

  const applyPreset = (days: number) => {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    setStart(toDate(startDate));
    setEnd(toDate(endDate));
  };

  const key = [start, end];

  const salesQuery = useQuery<SalesSummary>({
    queryKey: ['reports', 'sales', ...key],
    queryFn: async () => (await api.get('/reports/sales', { params: { start, end } })).data.summary,
  });

  const paymentsQuery = useQuery<PaymentRow[]>({
    queryKey: ['reports', 'payments', ...key],
    queryFn: async () => (await api.get('/reports/payments', { params: { start, end } })).data.data ?? [],
  });

  const staffQuery = useQuery<StaffRow[]>({
    queryKey: ['reports', 'staff', ...key],
    queryFn: async () => (await api.get('/reports/staff', { params: { start, end } })).data.data ?? [],
  });

  const loading = salesQuery.isLoading || paymentsQuery.isLoading || staffQuery.isLoading;
  const summary = salesQuery.data;

  const paymentTotal = (paymentsQuery.data ?? []).reduce((acc, p) => acc + Number(p.total || 0), 0);

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-charcoal">Reports</h1>
          <p className="text-sm text-gray-500 mt-1">Sales, payments and staff performance.</p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <div className="flex items-center gap-1 border border-border rounded-sm px-1 py-1 bg-white">
            {PRESETS.map((p) => (
              <Button
                key={p.label}
                variant="outline"
                size="sm"
                onClick={() => applyPreset(p.days)}
                className="h-7 px-2 text-xs"
              >
                {p.label}
              </Button>
            ))}
          </div>
          <label className="text-xs text-gray-500 sr-only">Start</label>
          <input
            type="date"
            value={start}
            onChange={(e) => setStart(e.target.value)}
            className="h-9 border border-border rounded-sm px-2 text-sm bg-white"
          />
          <span className="text-gray-400">→</span>
          <input
            type="date"
            value={end}
            onChange={(e) => setEnd(e.target.value)}
            className="h-9 border border-border rounded-sm px-2 text-sm bg-white"
          />
        </div>
      </div>

      {loading ? (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {[0, 1, 2, 3].map((i) => (
            <Card key={i}><CardContent><Skeleton className="h-16 w-full" /></CardContent></Card>
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <Card><CardContent>
            <p className="text-xs text-gray-500 uppercase tracking-wide">Orders</p>
            <p className="text-2xl font-mono font-bold text-charcoal mt-1">{summary?.orders ?? 0}</p>
          </CardContent></Card>
          <Card><CardContent>
            <p className="text-xs text-gray-500 uppercase tracking-wide">Gross Sales</p>
            <p className="text-2xl font-mono font-bold text-charcoal mt-1">{fmt(summary?.gross_sales ?? 0)}</p>
          </CardContent></Card>
          <Card><CardContent>
            <p className="text-xs text-gray-500 uppercase tracking-wide">Net Sales</p>
            <p className="text-2xl font-mono font-bold text-amber mt-1">{fmt(summary?.net_sales ?? 0)}</p>
          </CardContent></Card>
          <Card><CardContent>
            <p className="text-xs text-gray-500 uppercase tracking-wide">Avg Order Value</p>
            <p className="text-2xl font-mono font-bold text-charcoal mt-1">{fmt(summary?.aov ?? 0)}</p>
          </CardContent></Card>
        </div>
      )}

      {summary ? (
        <div className="flex items-center gap-4 text-xs text-gray-500">
          <span>Discounts given: <span className="font-mono text-charcoal">−{fmt(summary.discounts)}</span></span>
        </div>
      ) : null}

      <div className="grid lg:grid-cols-2 gap-6">
        <Card padding="none">
          <CardHeader><CardTitle>Payments by Method</CardTitle></CardHeader>
          <CardContent className="p-0">
            {paymentsQuery.data?.length ? (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Method</TableHead>
                    <TableHead className="text-right">Count</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paymentsQuery.data.map((p) => (
                    <TableRow key={p.method}>
                      <TableCell className="font-medium">{METHOD_LABELS[p.method] ?? p.method}</TableCell>
                      <TableCell className="text-right text-gray-600">{p.count}</TableCell>
                      <TableCell className="text-right font-mono">{fmt(p.total)}</TableCell>
                    </TableRow>
                  ))}
                  <TableRow>
                    <TableCell className="font-semibold">Total</TableCell>
                    <TableCell />
                    <TableCell className="text-right font-mono font-semibold">{fmt(paymentTotal)}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            ) : (
              <EmptyState title="No payments" description="No completed payments in this range." />
            )}
          </CardContent>
        </Card>

        <Card padding="none">
          <CardHeader><CardTitle>Staff Performance</CardTitle></CardHeader>
          <CardContent className="p-0">
            {staffQuery.data?.length ? (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Waiter</TableHead>
                    <TableHead className="text-right">Orders</TableHead>
                    <TableHead className="text-right">Gross</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {staffQuery.data.map((s) => (
                    <TableRow key={s.waiter_id ?? 'unknown'}>
                      <TableCell className="font-medium">
                        {s.waiter?.name ?? 'Unassigned'}
                      </TableCell>
                      <TableCell className="text-right text-gray-600">{s.orders}</TableCell>
                      <TableCell className="text-right font-mono">{fmt(s.gross)}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            ) : (
              <EmptyState title="No staff activity" description="No orders attributed in this range." />
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
