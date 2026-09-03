// Shared client-side validation helpers.

export interface PasswordStrengthResult {
  /** 0..4 — segment count to light up on the meter */
  score: number;
  label: string;
  tooShort: boolean;
}

export function passwordStrength(password: string): PasswordStrengthResult {
  if (!password) return { score: 0, label: 'Enter a password', tooShort: false };
  if (password.length < 8) return { score: 0, label: 'Too short', tooShort: true };

  const mixedCase = /[a-z]/.test(password) && /[A-Z]/.test(password);
  const digit = /\d/.test(password);
  const symbol = /[^A-Za-z0-9]/.test(password);

  let score = 1 + [mixedCase, digit, symbol].filter(Boolean).length;
  if (password.length >= 12) score = Math.min(4, score + 1);

  const labels = { 1: 'Weak', 2: 'Fair', 3: 'Good', 4: 'Strong' };
  return { score, label: labels[score as keyof typeof labels], tooShort: false };
}