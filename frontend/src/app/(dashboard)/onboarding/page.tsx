'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import api from '@/lib/axios';
import { useAuthStore } from '@/store/authStore';
import { sanitizeEmail, trimStrings } from '@/lib/sanitize';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

type Step = 'business' | 'branch' | 'tax' | 'invite';

export default function OnboardingPage() {
  const [step, setStep] = useState<Step | null>(null);
  const [loading, setLoading] = useState(false);
  const [orgId, setOrgId] = useState<number | null>(null);
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);
  const user = useAuthStore((s) => s.user);
  const token = useAuthStore((s) => s.token);

  // ── Adaptive steps ──────────────────────────────────────────────────────────
  // A brand-new signup already has an organization + Main Branch from
  // /auth/register, so only tax & invite steps remain. Account-less users walk
  // the full wizard and create their org/branch here.
  const hasOrg = Boolean(user?.organization_id);
  const steps: Step[] = hasOrg ? ['tax', 'invite'] : ['business', 'branch', 'tax', 'invite'];
  const effectiveOrgId = orgId ?? user?.organization_id ?? null;
  const initialStep = (step === null ? steps[0] : step) as Step;

  // Step 1 state
  const [businessName, setBusinessName] = useState('');
  const [businessType, setBusinessType] = useState('');

  // Step 2 state
  const [branchName, setBranchName] = useState('');
  const [branchAddress, setBranchAddress] = useState('');

  // Step 3 state
  const [taxPct, setTaxPct] = useState('0');
  const [servicePct, setServicePct] = useState('0');

  // Step 4 state
  const [inviteEmail, setInviteEmail] = useState('');
  const [inviteRole, setInviteRole] = useState('waiter');

  const stepIndex = steps.indexOf(initialStep);
  const progress = ((stepIndex + 1) / steps.length) * 100;

  const advance = (next: Step | null) => {
    if (!next) {
      router.push('/dashboard');
      return;
    }
    setStep(next);
  };

  const back = () => {
    const idx = steps.indexOf(initialStep);
    advance(idx > 0 ? steps[idx - 1] : null);
  };

  const handleStep1 = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await api.post('/organizations', trimStrings({
        name: businessName,
        type: businessType,
        currency: 'NGN',
      }));
      const id = res.data.data.id;
      setOrgId(id);
      // Update user in store with new org
      if (user && token) {
        setAuth({ ...user, organization_id: id }, token);
      }
      advance('branch');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleStep2 = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      await api.post('/branches', trimStrings({
        name: branchName || 'Main Branch',
        address: branchAddress,
      }));
      advance('tax');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleStep3 = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (effectiveOrgId) {
        await api.patch(`/organizations/${effectiveOrgId}`, {
          tax_percentage: parseFloat(taxPct),
          service_charge_percentage: parseFloat(servicePct),
        });
      }
      advance('invite');
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleStep4 = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (inviteEmail) {
        await api.post('/users', trimStrings({
          first_name: 'Invited',
          last_name: 'User',
          email: sanitizeEmail(inviteEmail),
          role: inviteRole,
        }));
      }
      router.push('/dashboard');
    } catch (err) {
      // Even if invite fails, go to dashboard
      router.push('/dashboard');
    } finally {
      setLoading(false);
    }
  };

  const stepLabels: Record<Step, string> = {
    business: 'Business Info',
    branch: 'First Branch',
    tax: 'Tax & Charges',
    invite: 'Invite Staff',
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="w-full max-w-lg">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-charcoal">
            Welcome to <span className="text-amber">Tavro</span>
          </h1>
          <p className="text-sm text-gray-500 mt-1">Let&apos;s set up your business in a few steps.</p>
        </div>

        {/* Progress */}
        <div className="mb-6">
          <div className="flex justify-between text-xs text-gray-400 mb-2">
            {steps.map((s, i) => (
              <span key={s} className={i <= stepIndex ? 'text-amber font-medium' : ''}>
                {stepLabels[s]}
              </span>
            ))}
          </div>
          <div className="w-full bg-gray-200 h-1">
            <div
              className="bg-amber h-1 transition-all duration-500"
              style={{ width: `${progress}%` }}
            />
          </div>
        </div>

        {/* Card */}
        <div className="bg-white border border-gray-200 p-6">
          {/* Step 1: Business Info */}
          {initialStep === 'business' && (
            <form onSubmit={handleStep1} className="space-y-4">
              <h2 className="text-lg font-semibold text-charcoal">Tell us about your business</h2>
              <div>
                <label className="block text-sm font-medium mb-1">Business Name</label>
                <Input value={businessName} onChange={(e) => setBusinessName(e.target.value)} placeholder="e.g. The Grand Lounge" required />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Business Type</label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-amber text-sm bg-white"
                  value={businessType}
                  onChange={(e) => setBusinessType(e.target.value)}
                  required
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
              <div className="pt-2 flex justify-end">
                <Button type="submit" disabled={loading}>{loading ? 'Saving...' : 'Continue →'}</Button>
              </div>
            </form>
          )}

          {/* Step 2: First Branch */}
          {initialStep === 'branch' && (
            <form onSubmit={handleStep2} className="space-y-4">
              <h2 className="text-lg font-semibold text-charcoal">Set up your first branch</h2>
              <div>
                <label className="block text-sm font-medium mb-1">Branch Name</label>
                <Input value={branchName} onChange={(e) => setBranchName(e.target.value)} placeholder="e.g. Main Branch, Victoria Island" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Address <span className="text-gray-400">(optional)</span></label>
                <Input value={branchAddress} onChange={(e) => setBranchAddress(e.target.value)} placeholder="e.g. 12 Adeola Odeku Street, Lagos" />
              </div>
              <div className="pt-2 flex justify-between">
                <Button type="button" variant="secondary" onClick={back}>← Back</Button>
                <Button type="submit" disabled={loading}>{loading ? 'Saving...' : 'Continue →'}</Button>
              </div>
            </form>
          )}

          {/* Step 3: Tax & Service Charge */}
          {initialStep === 'tax' && (
            <form onSubmit={handleStep3} className="space-y-4">
              <h2 className="text-lg font-semibold text-charcoal">Tax & Service Charge</h2>
              <p className="text-sm text-gray-500">These will be applied automatically to orders. You can change them later in settings.</p>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">VAT / Tax %</label>
                  <Input type="number" min="0" max="100" step="0.1" value={taxPct} onChange={(e) => setTaxPct(e.target.value)} />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">Service Charge %</label>
                  <Input type="number" min="0" max="100" step="0.1" value={servicePct} onChange={(e) => setServicePct(e.target.value)} />
                </div>
              </div>
              <div className="pt-2 flex justify-between">
                <Button type="button" variant="secondary" onClick={back}>← Back</Button>
                <Button type="submit" disabled={loading}>{loading ? 'Saving...' : 'Continue →'}</Button>
              </div>
            </form>
          )}

          {/* Step 4: Invite First Staff */}
          {initialStep === 'invite' && (
            <form onSubmit={handleStep4} className="space-y-4">
              <h2 className="text-lg font-semibold text-charcoal">Invite your first team member</h2>
              <p className="text-sm text-gray-500">You can skip this and invite staff later from the Team page.</p>
              <div>
                <label className="block text-sm font-medium mb-1">Email Address</label>
                <Input type="email" value={inviteEmail} onChange={(e) => setInviteEmail(e.target.value)} placeholder="colleague@example.com" />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Role</label>
                <select
                  className="w-full px-3 py-2 border border-gray-300 focus:outline-none focus:ring-1 focus:ring-amber text-sm bg-white"
                  value={inviteRole}
                  onChange={(e) => setInviteRole(e.target.value)}
                >
                  <option value="general_manager">General Manager</option>
                  <option value="branch_manager">Branch Manager</option>
                  <option value="cashier">Cashier</option>
                  <option value="waiter">Waiter</option>
                  <option value="bartender">Bartender</option>
                </select>
              </div>
              <div className="pt-2 flex justify-between">
                <Button type="button" variant="secondary" onClick={back}>← Back</Button>
                <div className="flex gap-2">
                  <Button type="button" variant="secondary" onClick={() => router.push('/dashboard')}>Skip</Button>
                  <Button type="submit" disabled={loading}>{loading ? 'Inviting...' : 'Finish Setup'}</Button>
                </div>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}