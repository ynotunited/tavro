'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { adminHref } from '@/lib/adminApi';

export default function AdminPanelRootPage() {
  const router = useRouter();

  useEffect(() => {
    router.replace(adminHref('/login'));
  }, [router]);

  return null;
}
