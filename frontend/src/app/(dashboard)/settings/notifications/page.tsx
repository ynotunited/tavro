'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';

interface ChannelSettings {
  telegram_configured: boolean;
  bot_username: string | null;
  connected: boolean;
  sales_reports_enabled: boolean;
  sales_report_frequency: 'hourly' | 'daily' | 'weekly';
  last_sent_at: string | null;
}

interface PairResult {
  code: string;
  expires_at: string;
  bot_username: string | null;
  telegram_configured: boolean;
  instructions: string;
  connected: boolean;
}

const FREQUENCIES = [
  { value: 'hourly', label: 'Every hour', hint: 'Fresh digest each hour the kitchen/bar is running.' },
  { value: 'daily', label: 'Every day', hint: 'One digest after the day wraps — the default.' },
  { value: 'weekly', label: 'Every week', hint: 'Weekly round-up, best for owners who check in occasionally.' },
];

export default function NotificationsSettingsPage() {
  const queryClient = useQueryClient();
  const [pairing, setPairing] = useState<PairResult | null>(null);
  const [frequency, setFrequency] = useState<'hourly' | 'daily' | 'weekly'>('daily');

  const { data: settings, isLoading } = useQuery<ChannelSettings>({
    queryKey: ['notification-channels'],
    queryFn: async () => {
      const res = await api.get('/notification-channels');
      return res.data.data;
    },
  });

  const enabled = settings?.sales_reports_enabled ?? false;
  const connected = settings?.connected ?? false;

  const pairMutation = useMutation({
    mutationFn: () => api.post('/notification-channels/telegram/pair'),
    onSuccess: (res) => setPairing(res.data.data),
  });

  const disconnectMutation = useMutation({
    mutationFn: () => api.post('/notification-channels/telegram/disconnect'),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notification-channels'] });
      setPairing(null);
    },
  });

  const saveMutation = useMutation({
    mutationFn: (data: { sales_reports_enabled: boolean; sales_report_frequency: string }) =>
      api.patch('/notification-channels', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notification-channels'] }),
  });

  const testMutation = useMutation({
    mutationFn: () => api.post('/notification-channels/telegram/test'),
    onError: (err: { response?: { data?: { message?: string } } }) =>
      err.response?.data?.message,
  });

  const toggleReport = () => {
    if (!connected) return;
    saveMutation.mutate({ sales_reports_enabled: !enabled, sales_report_frequency: frequency });
  };

  const error =
    (testMutation.error as { response?: { data?: { message?: string } } })?.response?.data?.message
    || (saveMutation.error as { response?: { data?: { message?: string } } })?.response?.data?.message
    || null;

  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Notifications</h1>
        <p className="text-sm text-gray-500">
          Periodic sales digests sent straight to your phone — free, on Telegram.
        </p>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
              <CardTitle>Link Telegram</CardTitle>
              <p className="text-xs text-gray-500 mt-0.5">
                Your pocket cash-register. No WhatsApp Business fees — Telegram is the totally free channel.
              </p>
            </div>
            {!isLoading && (
              <Badge variant={connected ? 'success' : 'warning'}>
                {connected ? 'Connected' : settings?.telegram_configured ? 'Not connected' : 'Bot not configured'}
              </Badge>
            )}
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? (
            <p className="text-sm text-gray-500">Loading…</p>
          ) : (
            <>
              {!settings?.telegram_configured && (
                <p className="text-sm text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2">
                  The Tavro Telegram bot is not configured on this server yet
                  (set <code className="text-xs">TELEGRAM_BOT_TOKEN</code>). You can still pair once it is.
                </p>
              )}

              {!pairing && !connected && (
                <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                  <Button onClick={() => pairMutation.mutate()} disabled={pairMutation.isPending}>
                    {pairMutation.isPending ? 'Generating…' : 'Scan a pairing code'}
                  </Button>
                  <span className="text-sm text-gray-500">
                    You&apos;ll DM the code to the bot — no secrets, no middleman.
                  </span>
                </div>
              )}

              {pairing && !connected && (
                <div className="border border-gray-200 bg-gray-50 p-4 space-y-3">
                  <p className="text-sm text-gray-700">{pairing.instructions}</p>
                  <p className="font-mono text-3xl font-bold tracking-widest text-charcoal select-all">
                    tavro {pairing.code}
                  </p>
                  <p className="text-xs text-gray-500">
                    Expires {new Date(pairing.expires_at).toLocaleTimeString()}. Hard to type? Tap to copy:
                  </p>
                  <Button size="sm" variant="secondary" onClick={() => { void navigator.clipboard?.writeText(pairing.instructions); }}>
                    Copy instructions
                  </Button>
                </div>
              )}

              {connected && (
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <p className="text-sm font-medium text-charcoal">Chat linked to your Telegram</p>
                    <p className="text-xs text-gray-500">
                      Sales digests will arrive here. Sent to you — and only managed by you.
                    </p>
                  </div>
                  <Button variant="danger" size="sm" onClick={() => disconnectMutation.mutate()}>
                    Disconnect
                  </Button>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Sales digests</CardTitle>
          <p className="text-xs text-gray-500 mt-0.5">
            Orders, net sales, cash collected and your top sellers — auto-pushed.
          </p>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-charcoal">Scheduled reports</p>
              <p className="text-xs text-gray-500">
                {enabled ? `On — last digest ${settings?.last_sent_at ? new Date(settings.last_sent_at).toLocaleString() : 'queued'}.` : 'Currently off.'}
              </p>
            </div>
            <Button
              variant={enabled ? 'secondary' : 'primary'}
              size="sm"
              disabled={!connected || saveMutation.isPending}
              onClick={toggleReport}
            >
              {enabled ? 'Turn off' : 'Turn on'}
            </Button>
          </div>

          <div className="space-y-2">
            <p className="text-sm font-medium text-charcoal">Frequency</p>
            {FREQUENCIES.map((f) => (
              <label
                key={f.value}
                onClick={() => { setFrequency(f.value as 'hourly' | 'daily' | 'weekly'); if (enabled && connected) saveMutation.mutate({ sales_reports_enabled: true, sales_report_frequency: f.value }); }}
                className={`flex items-start gap-3 border p-3 cursor-pointer transition-colors ${frequency === f.value ? 'border-amber bg-amber-50' : 'border-gray-200 hover:bg-gray-50'}`}
              >
                <input
                  type="radio"
                  name="frequency"
                  value={f.value}
                  checked={frequency === f.value}
                  onChange={() => setFrequency(f.value as 'hourly' | 'daily' | 'weekly')}
                  className="mt-0.5 accent-amber"
                />
                <span>
                  <span className="block text-sm font-medium text-charcoal">{f.label}</span>
                  <span className="block text-xs text-gray-500">{f.hint}</span>
                </span>
              </label>
            ))}
          </div>

          <div className="flex items-center gap-3 pt-1">
            <Button variant="outline" size="sm" disabled={!connected || testMutation.isPending} onClick={() => testMutation.mutate()}>
              {testMutation.isPending ? 'Sending…' : 'Send a test digest'}
            </Button>
            {testMutation.isSuccess && <span className="text-sm text-green-600">Delivered — check your Telegram.</span>}
            {error && <span className="text-sm text-red-600">{error}</span>}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}