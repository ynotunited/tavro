'use client';

import { useCallback, useEffect, useState } from 'react';
import adminApi from '@/lib/adminApi';
import { Card, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/Table';
import { Skeleton } from '@/components/ui/Skeleton';

interface AuditRow {
  id: number;
  admin_user_id: number | null;
  action: string;
  entity_type: string | null;
  entity_id: number | null;
  method: string | null;
  path: string | null;
  request_payload: Record<string, unknown> | null;
  status_code: number | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  admin: { id: number; name: string; email: string } | null;
}

interface AuditResponse {
  data: AuditRow[];
  current_page: number;
  last_page: number;
  total: number;
}

function statusColor(code: number | null): 'success' | 'error' | 'warning' | 'default' {
  if (code == null) return 'default';
  if (code >= 500) return 'error';
  if (code === 401 || code === 403 || code === 429) return 'warning';
  return 'success';
}

export default function AdminAuditLogsPage() {
  const [rows, setRows] = useState<AuditRow[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async (pageNum: number) => {
    setLoading(true);
    try {
      const res = await adminApi.get('/audit-logs', { params: { page: pageNum } });
      const data = res.data?.data as AuditResponse;
      setRows(data.data ?? []);
      setPage(data.current_page ?? pageNum);
      setLastPage(data.last_page ?? 1);
      setTotal(data.total ?? 0);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const res = await adminApi.get('/audit-logs', { params: { page: 1 } });
        if (!alive) return;
        const data = res.data?.data as AuditResponse;
        setRows(data.data ?? []);
        setPage(data.current_page ?? 1);
        setLastPage(data.last_page ?? 1);
        setTotal(data.total ?? 0);
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-primary">Admin Audit Log</h1>
        <p className="text-sm text-muted">Every admin action, with actor, timestamp, and source.</p>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="grid grid-cols-1 gap-4 p-4">
              <Skeleton className="h-32 w-full" />
            </div>
          ) : rows.length === 0 ? (
            <p className="p-6 text-sm text-muted">No audit entries yet.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>When</TableHead>
                  <TableHead>Admin</TableHead>
                  <TableHead>Action</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Path</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>IP</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="whitespace-nowrap text-xs">
                      {new Date(row.created_at).toLocaleString()}
                    </TableCell>
                    <TableCell>
                      <div className="text-sm">{row.admin?.name ?? '—'}</div>
                      <div className="text-xs text-muted">{row.admin?.email ?? ''}</div>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{row.action}</TableCell>
                    <TableCell className="text-xs">{row.method ?? '—'}</TableCell>
                    <TableCell className="max-w-[16rem] truncate font-mono text-xs" title={row.path ?? ''}>
                      {row.path ?? '—'}
                    </TableCell>
                    <TableCell>
                      <Badge variant={statusColor(row.status_code)}>{row.status_code ?? '—'}</Badge>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{row.ip_address ?? '—'}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {!loading && total > 0 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted">
            {total} total · page {page} of {lastPage}
          </p>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => load(page - 1)}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= lastPage}
              onClick={() => load(page + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
