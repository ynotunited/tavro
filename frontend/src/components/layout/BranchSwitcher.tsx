'use client';

import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { useAuthStore } from '@/store/authStore';

interface Branch {
  id: number;
  name: string;
}

export function BranchSwitcher() {
  const { activeBranchId, setActiveBranch } = useAuthStore();

  const { data: branches } = useQuery<Branch[]>({
    queryKey: ['branches'],
    queryFn: async () => {
      const res = await api.get('/branches');
      return res.data.data;
    },
  });

  // Auto-select first branch if none selected
  useEffect(() => {
    if (!activeBranchId && branches?.length) {
      setActiveBranch(branches[0].id);
    }
  }, [branches, activeBranchId, setActiveBranch]);

  if (!branches?.length) return null;

  return (
    <div>
      <label className="text-xs text-gray-400 uppercase tracking-wider mb-1 block">Active Branch</label>
      <select
        className="w-full bg-gray-800 border border-gray-700 text-sm p-2 text-white focus:outline-none focus:ring-1 focus:ring-amber"
        value={activeBranchId ?? ''}
        onChange={(e) => setActiveBranch(Number(e.target.value))}
      >
        {branches.map((branch) => (
          <option key={branch.id} value={branch.id}>
            {branch.name}
          </option>
        ))}
      </select>
    </div>
  );
}
