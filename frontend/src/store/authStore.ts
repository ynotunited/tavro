import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  role: string; // admin, manager, staff, etc.
  organization_id: number | null;
  branch_id: number | null;
  // add other fields as necessary
}

interface AuthState {
  user: User | null;
  token: string | null;
  signingSecret: string | null;
  activeBranchId: number | null;
  setAuth: (user: User, token: string, signingSecret?: string | null) => void;
  setActiveBranch: (branchId: number) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      signingSecret: null,
      activeBranchId: null,
      setAuth: (user, token, signingSecret) =>
        set({
          user,
          token,
          // Preserve the existing secret when one is not supplied (e.g. onboarding)
          signingSecret:
            typeof signingSecret === 'string' ? signingSecret : get().signingSecret,
        }),
      setActiveBranch: (branchId) => set({ activeBranchId: branchId }),
      logout: () =>
        set({ user: null, token: null, signingSecret: null, activeBranchId: null }),
    }),
    {
      name: 'tavro-auth',
    }
  )
);