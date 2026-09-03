import React from 'react';

export interface ProgressProps extends React.HTMLAttributes<HTMLDivElement> {
  value?: number;
}

export function Progress({ value = 0, className = '', children, ...props }: ProgressProps) {
  return (
    <div
      className={`relative h-2 w-full overflow-hidden bg-charcoal-100 ${className}`}
      {...props}
    >
      {children ? (
        children
      ) : (
        <div
          className="h-full bg-amber transition-all"
          style={{ width: `${Math.min(100, Math.max(0, value))}%` }}
        />
      )}
    </div>
  );
}
