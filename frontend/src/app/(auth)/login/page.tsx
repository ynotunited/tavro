'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useSearchParams, useRouter } from 'next/navigation';
import { useAuthStore } from '@/store/authStore';
import api from '@/lib/axios';
import { AxiosError } from 'axios';
import Logo from '@/components/Logo';
import { sanitizeEmail } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [resent, setResent] = useState<string | null>(null);

  const setAuth = useAuthStore((state) => state.setAuth);
  const router = useRouter();
  const searchParams = useSearchParams();
  const resetSuccess = searchParams.get('reset') === 'success';

  const needsVerification = error.includes('verify your email');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setResent(null);

    try {
      const response = await api.post('/auth/login', { email: sanitizeEmail(email), password });

      if (response.data.data?.token) {
        setAuth(
          response.data.data.user,
          response.data.data.token,
          response.data.data.signing_secret
        );
        if (!response.data.data.user.organization_id) {
          router.push('/onboarding');
        } else {
          router.push('/dashboard');
        }
      }
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      const message =
        axiosErr.response?.status === 403
          ? axiosErr.response?.data?.message || 'Please verify your email address before signing in.'
          : axiosErr.response?.data?.message || 'Invalid email or password.';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    setResending(true);
    setResent(null);
    try {
      await api.post('/auth/email/verification/resend', { email: sanitizeEmail(email) });
      setResent('If an account needs verification, a new link has been sent.');
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      setError(axiosErr.response?.data?.message || 'Something went wrong. Please try again.');
    } finally {
      setResending(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4 text-slate-900">
      <div className="w-full max-w-md bg-white p-8 border border-slate-200 shadow-sm">
        {/* Logo / Brand */}
        <div className="text-center mb-8">
          <Logo variant="dark" width={180} className="mx-auto" />
          <p className="text-slate-600 mt-3 text-sm">Sign in to your account</p>
        </div>

        {/* Success banner from password reset */}
        {resetSuccess && (
          <div className="mb-4 p-3 bg-green-50 text-green-700 text-sm border border-green-200">
            ✓ Password reset successfully. Please sign in with your new password.
          </div>
        )}

        {/* Error banner */}
        {error && !needsVerification && (
          <div className="mb-4 p-3 bg-red-50 text-red-600 text-sm border border-red-100">
            {error}
          </div>
        )}

        {/* Verify-your-email gate */}
        {needsVerification && (
          <div className="mb-4 p-4 bg-amber-50 text-amber-800 text-sm border border-amber-200">
            <p className="font-medium mb-1">Please verify your email address</p>
            <p className="mb-3">
              Check your inbox (and spam folder) for the confirmation link we sent to{' '}
              <strong>{email || 'your address'}</strong>.
            </p>
            <button
              type="button"
              onClick={handleResend}
              disabled={resending || !email}
              className="text-sm font-medium text-amber-700 hover:underline disabled:opacity-50"
              id="resend-verification-btn"
            >
              {resending ? 'Sending...' : 'Resend verification email'}
            </button>
            {resent && <p className="mt-2 text-green-700">✓ {resent}</p>}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-800 mb-1">Email Address</label>
            <Input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="you@example.com"
              id="email"
            />
          </div>
          <div>
            <div className="flex items-center justify-between mb-1">
              <label className="block text-sm font-medium text-slate-800">Password</label>
              <Link href="/forgot-password" className="text-xs text-amber-600 hover:underline">
                Forgot password?
              </Link>
            </div>
            <Input
              type="password"
              toggle
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="••••••••"
              id="password"
            />
          </div>
          <div className="pt-2">
            <Button type="submit" className="w-full" disabled={loading} id="login-btn">
              {loading ? 'Signing in...' : 'Sign In'}
            </Button>
          </div>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          New to Tavro?{' '}
          <Link href="/register" className="text-amber-600 hover:underline">
            Create an account
          </Link>
        </p>
      </div>
    </div>
  );
}
