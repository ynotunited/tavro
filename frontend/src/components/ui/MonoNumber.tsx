import React from 'react';

export interface MonoNumberProps {
  amount: number;
  currency?: string;
  className?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}

export function MonoNumber({ amount, currency = '₦', className = '', size = 'md' }: MonoNumberProps) {
  const sizes = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-xl',
    xl: 'text-3xl',
  };
  
  const formattedAmount = new Intl.NumberFormat('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);

  return (
    <span className={`font-mono font-semibold tracking-wider ${sizes[size]} ${className}`}>
      {currency}{formattedAmount}
    </span>
  );
}
