import React from 'react';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger' | 'default' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  fullWidth?: boolean;
  asChild?: boolean;
}

export function Button({ 
  children, 
  variant = 'primary',
  size = 'md',
  fullWidth = false,
  asChild = false,
  className = '',
  ...props 
}: ButtonProps) {
  
  const baseStyles = 'inline-flex items-center justify-center font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none rounded-none';
  
  const variants: Record<string, string> = {
    primary: 'bg-amber-600 text-slate-900 hover:bg-amber-700',
    default: 'bg-amber-600 text-slate-900 hover:bg-amber-700',
    secondary: 'bg-white text-slate-900 border border-slate-300 hover:bg-slate-50',
    outline: 'bg-transparent text-slate-900 border border-slate-300 hover:bg-slate-50',
    danger: 'bg-red-600 text-white hover:bg-red-700',
  };
  
  const sizes: Record<string, string> = {
    sm: 'h-8 px-4 text-xs',
    md: 'h-10 px-5 text-sm',
    lg: 'h-12 px-6 text-base',
  };

  const combinedClassName = `${baseStyles} ${variants[variant] || variants.primary} ${sizes[size]} ${fullWidth ? 'w-full' : ''} ${className}`;

  if (asChild && React.isValidElement(children)) {
    const child = children as React.ReactElement<Record<string, unknown>>;
    const childClassName = typeof child.props?.className === 'string' ? child.props.className : '';
    return React.cloneElement(child, {
      className: `${combinedClassName} ${childClassName}`.trim(),
      ...props,
    });
  }

  return (
    <button 
      className={combinedClassName}
      {...props}
    >
      {children}
    </button>
  );
}
