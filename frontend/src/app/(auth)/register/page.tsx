'use client';

import { useState } from 'react';
import Link from 'next/link';
import api from '@/lib/axios';
import { AxiosError } from 'axios';
import Logo from '@/components/Logo';
import { sanitizeEmail, trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { PasswordStrengthMeter } from '@/components/ui/PasswordStrengthMeter';

const PASSWORD_HINT = 'At least 8 characters with uppercase, lowercase, number, and symbol.';

export default function RegisterPage() {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [businessName, setBusinessName] = useState('');
  const [businessType, setBusinessType] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [registeredEmail, setRegisteredEmail] = useState<string | null>(null);

  // Live validation
  const passwordsMatch = !confirm || password === confirm;

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    if (password !== confirm) {
      setError('Passwords do not match.');
      setLoading(false);
      return;
    }

    try {
      const response = await api.post('/auth/register', trimStrings({
        first_name: firstName,
        last_name: lastName,
        email: sanitizeEmail(email),
        password,
        password_confirmation: confirm,
        business_name: businessName,
        business_type: businessType || undefined,
      }));

      // Registration no longer signs you in — confirm your email first.
      setRegisteredEmail(response.data.data?.user?.email ?? sanitizeEmail(email));
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string; errors?: Record<string, string[]> }>;
      const message = axiosErr.response?.data?.message;
      const firstError = Object.values(axiosErr.response?.data?.errors || {}).flat()[0];
      setError(firstError || message || 'Something went wrong. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (registeredEmail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4 text-slate-900">
        <div className="w-full max-w-lg bg-white p-8 border border-slate-200 shadow-sm">
            <div className="text-center mb-8">
              <Logo variant="dark" width={200} className="mx-auto" />
            </div>
          <div className="mb-4 p-4 bg-green-50 text-green-700 text-sm border border-green-200">
            ✓ Your restaurant is ready. We&apos;ve sent a verification link to{' '}
            <strong>{registeredEmail}</strong>. Confirm your email to sign in.
          </div>
          <p className="text-sm text-slate-600 mb-4">
            Didn&apos;t get it? Check spam or junk, and give it a minute. You can resend the
            link from the sign-in page.
          </p>
          <Link href="/login">
            <Button className="w-full" id="register-go-login-btn">Go to sign in</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4 text-slate-900">
      <div className="w-full max-w-lg bg-white p-8 border border-slate-200 shadow-sm">
          {/* Logo / Brand */}
          <div className="text-center mb-8">
            <Logo variant="dark" width={200} className="mx-auto" />
            <p className="text-slate-600 mt-2 text-sm">Start your free 14-day trial. No credit card required.</p>
        </div>

        {/* Error banner */}
        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-600 text-sm border border-red-100">
            {error}
          </div>
        )}

        <form onSubmit={handleRegister} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="First Name"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              required
              placeholder="Ada"
              id="first_name"
            />
            <Input
              label="Last Name"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              required
              placeholder="Nwosu"
              id="last_name"
            />
          </div>
          <Input
            label="Email Address"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            placeholder="you@example.com"
            id="email"
          />
          <Input
            label="Business Name"
            value={businessName}
            onChange={(e) => setBusinessName(e.target.value)}
            required
            placeholder="e.g. The Golden Fork"
            id="business_name"
          />
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-slate-900">Business Type</label>
            <select
              className="flex h-10 w-full rounded-none border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
              value={businessType}
              onChange={(e) => setBusinessType(e.target.value)}
              id="business_type"
            >
              <option value="">Select a type...</option>
              <option value="Restaurant">Restaurant</option>
              <option value="Bar">Bar</option>
              <option value="Lounge">Lounge</option>
              <option value="Club">Club</option>
              <option value="Hotel">Hotel</option>
              <option value="Group">Group / Chain</option>
            </select>
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
          {!passwordsMatch && (
            <p className="text-xs text-red-500 mt-1">Passwords do not match.</p>
          )}
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
          <p className="text-xs text-slate-500">{PASSWORD_HINT}</p>

          <div className="pt-2">
            <Button type="submit" className="w-full" disabled={loading} id="register-btn">
              {loading ? 'Creating your account...' : 'Create Account'}
            </Button>
          </div>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          Already have an account?{' '}
          <Link href="/login" className="text-amber-600 hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}