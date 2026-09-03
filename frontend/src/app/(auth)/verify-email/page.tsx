'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import api from '@/lib/axios';
import { AxiosError } from 'axios';
import Logo from '@/components/Logo';
import { Button } from '@/components/ui/Button';

type VerifyState = 'verifying' | 'verified' | 'invalid';

export default function VerifyEmailPage() {
  const searchParams = useSearchParams();
  const signedUrl = searchParams.get('url');

  const [state, setState] = useState<VerifyState>('verifying');
  const [message, setMessage] = useState('');

  useEffect(() => {
    let cancelled = false;

    async function run() {
      if (!signedUrl) {
        if (!cancelled) {
          setState('invalid');
          setMessage('This verification link is incomplete or invalid.');
        }
        return;
      }

      try {
        const res = await api.get(signedUrl);
        if (!cancelled) {
          setState('verified');
          setMessage(res.data?.message || 'Email verified successfully.');
        }
      } catch (err) {
        if (cancelled) return;

        const axiosErr = err as AxiosError<{ message?: string }>;
        const serverMessage = axiosErr.response?.data?.message;
        const isAlreadyVerified =
          axiosErr.response?.status === 422 && (serverMessage || '').includes('already verified');

        if (isAlreadyVerified) {
          setState('verified');
          setMessage(serverMessage ?? '');
        } else {
          setState('invalid');
          setMessage(
            serverMessage ||
              'This verification link is invalid or has expired. You can request a new one from the sign-in page.'
          );
        }
      }
    }

    run();

    return () => {
      cancelled = true;
    };
  }, [signedUrl]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4 text-slate-900">
      <div className="w-full max-w-md bg-white p-8 border border-slate-200 shadow-sm">
          <div className="text-center mb-8">
            <Logo variant="dark" width={180} className="mx-auto" />
          </div>

        {state === 'verifying' && (
          <div className="text-center">
            <p className="text-sm text-slate-600">Verifying your email address...</p>
          </div>
        )}

        {state === 'verified' && (
          <div>
            <div className="mb-4 p-4 bg-green-50 text-green-700 text-sm border border-green-200">
              ✓ {message}
            </div>
            <p className="text-sm text-slate-600 mb-4">
              Your email is confirmed. You can now sign in to your account.
            </p>
            <Link href="/login">
              <Button className="w-full" id="verify-go-login-btn">Sign in to Tavro</Button>
            </Link>
          </div>
        )}

        {state === 'invalid' && (
          <div>
            <div className="mb-4 p-4 bg-red-50 text-red-600 text-sm border border-red-100">
              {message}
            </div>
            <p className="text-sm text-slate-600 mb-4">
              If this keeps happening, go to the sign-in page and use “Resend verification email”.
            </p>
            <Link href="/login">
              <Button className="w-full" id="verify-go-login-invalid-btn">Go to sign in</Button>
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}