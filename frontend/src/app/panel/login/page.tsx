'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import axios from 'axios';
import adminApi, { adminHref } from '@/lib/adminApi';
import Logo from '@/components/Logo';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

/**
 * Admin (dev-company) login. Authenticates against the dedicated 'admin'
 * session guard. This screen is standalone — no sidebar, no auth gate.
 */
export default function AdminLoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await adminApi.post('/login', { email: email.trim().toLowerCase(), password });
      router.push(adminHref('/dashboard'));
      router.refresh();
    } catch (err) {
      const status = axios.isAxiosError(err) ? (err.response?.status ?? 0) : 0;
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) || 'Invalid credentials.';
      if (status === 429) {
        setError('Too many login attempts. Please try again later.');
      } else {
        setError(String(message));
      }
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-charcoal p-4 text-white">
      <div className="w-full max-w-md bg-surface text-primary border border-border p-8 shadow-sm">
        <div className="text-center mb-8">
          <Logo variant="dark" width={180} className="mx-auto" />
          <p className="mt-2 text-sm text-muted">Ops Admin Panel</p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-700 text-sm border border-red-200">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-primary mb-1">Admin Email</label>
            <Input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="admin@tavro.dev"
              autoComplete="username"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-primary mb-1">Password</label>
            <Input
              type="password"
              toggle
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="••••••••"
              autoComplete="current-password"
            />
          </div>
          <div className="pt-2">
            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? 'Signing in…' : 'Sign In'}
            </Button>
          </div>
        </form>

        <p className="mt-6 text-center text-xs text-muted">
          Authorized dev-company personnel only. All actions are audited.
        </p>
      </div>
    </div>
  );
}
