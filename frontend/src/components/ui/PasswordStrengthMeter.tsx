'use client';

import { passwordStrength } from '@/lib/validation';

const SEGMENT_COLORS = {
  0: 'bg-red-500',
  1: 'bg-red-500',
  2: 'bg-amber-500',
  3: 'bg-lime-500',
  4: 'bg-emerald-500',
} as const;

const LABEL_COLORS = {
  0: 'text-red-500',
  1: 'text-red-500',
  2: 'text-amber-600',
  3: 'text-lime-600',
  4: 'text-emerald-600',
} as const;

export function PasswordStrengthMeter({ value }: { value: string }) {
  const { score, label, tooShort } = passwordStrength(value);

  if (!value) return null;

  return (
    <div>
      <div className="flex gap-1" aria-hidden="true">
        {[1, 2, 3, 4].map((segment) => (
          <div
            key={segment}
            className={`h-1 flex-1 rounded-none transition-colors ${
              segment <= score ? SEGMENT_COLORS[score as keyof typeof SEGMENT_COLORS] : 'bg-slate-200'
            }`}
          />
        ))}
      </div>
      <p className={`text-xs mt-1 ${LABEL_COLORS[score as keyof typeof LABEL_COLORS]}`}>
        Password strength: {label}
        {tooShort && <span className="text-slate-400"> — at least 8 characters</span>}
      </p>
    </div>
  );
}