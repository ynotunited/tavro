'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/store/authStore';
import api from '@/lib/axios';
import { AxiosError } from 'axios';
import Logo from '@/components/Logo';
import { sanitizeEmail, trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { PasswordStrengthMeter } from '@/components/ui/PasswordStrengthMeter';

export default function InvitePage() {
  const searchParams = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const email = searchParams.get('email') ?? '';

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const setAuth = useAuthStore((state) => state.setAuth);
  const router = useRouter();

  const handleAccept = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    if (password !== confirm) {
      setError('Passwords do not match.');
      setLoading(false);
      return;
    }

    try {
      const response = await api.post('/auth/invite/accept', trimStrings({
        token,
        email: sanitizeEmail(email),
        first_name: firstName || undefined,
        last_name: lastName || undefined,
        password,
        password_confirmation: confirm,
      }));

      if (response.data.data?.token) {
        setAuth(
          response.data.data.user,
          response.data.data.token,
          response.data.data.signing_secret
        );
        router.push('/dashboard');
      }
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: Record<string, string[]> }>;
      const message = axiosErr.response?.data?.message;
      const firstError = Object.values(axiosErr.response?.data?.errors || {}).flat()[0];
      setError(firstError || message || 'Something went wrong. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4 text-slate-900">
      <div className="w-full max-w-md bg-white p-8 border border-slate-200 shadow-sm">
          <div className="text-center mb-8">
            <Logo variant="dark" width={180} className="mx-auto" />
            <p className="text-slate-600 mt-2 text-sm">Accept your invitation</p>
        </div>

        {!token || !email ? (
          <div className="p-3 bg-red-50 text-red-600 text-sm border border-red-100">
            This invitation link looks incomplete. Please ask your manager to resend it.
          </div>
        ) : (
          <>
            {error && (
              <div className="mb-4 p-3 bg-red-50 text-red-600 text-sm border border-red-100">
                {error}
              </div>
            )}

            <form onSubmit={handleAccept} className="space-y-4">
              <Input type="email" value={email} disabled label="Email Address" />
              <div className="grid grid-cols-2 gap-4">
                <Input
                  label="First Name"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                  placeholder="Your first name"
                  id="first_name"
                />
                <Input
                  label="Last Name"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                  placeholder="Your last name"
                  id="last_name"
                />
              </div>
              <Input
                label="Password"
                type="password"
                toggle
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="••••••••"
                id="password"
              />
              <PasswordStrengthMeter value={password} />
              <Input
                label="Confirm Password"
                type="password"
                toggle
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                required
                placeholder="••••••••"
                id="password_confirmation"
              />
              <p className="text-xs text-slate-500">At least 8 characters with uppercase, lowercase, number, and symbol.</p>

              <div className="pt-2">
                <Button type="submit" className="w-full" disabled={loading} id="accept-btn">
                  {loading ? 'Setting up...' : 'Set Password & Continue'}
                </Button>
              </div>
            </form>
          </>
        )}

        <p className="mt-6 text-center text-sm text-slate-600">
          Already accepted?{' '}
          <Link href="/login" className="text-amber-600 hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}