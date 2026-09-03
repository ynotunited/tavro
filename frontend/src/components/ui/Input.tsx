import React, { forwardRef, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  fullWidth?: boolean;
  /** Render a show/hide toggle on `type="password"` inputs */
  toggle?: boolean;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className = '', label, error, fullWidth = true, toggle = false, type, ...props }, ref) => {
    const [show, setShow] = useState(false);
    const isSecret = type === 'password';
    const showToggle = toggle && isSecret;

    const widthClass = fullWidth ? 'w-full' : '';
    const baseStyles = 'flex h-10 w-full rounded-none border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent disabled:cursor-not-allowed disabled:opacity-50 transition-shadow';
    const errorStyles = error ? 'border-red-500 focus:ring-red-500' : '';
    const togglePadding = showToggle ? 'pr-10' : '';

    return (
      <div className={`flex flex-col gap-1.5 ${widthClass}`}>
        {label && (
          <label className="text-sm font-medium text-slate-900">
            {label}
          </label>
        )}
        <div className="relative">
          <input
            ref={ref}
            type={show ? 'text' : type}
            className={`${baseStyles} ${togglePadding} ${errorStyles} ${widthClass} ${className}`}
            {...props}
          />
          {showToggle && (
            <button
              type="button"
              onClick={() => setShow((s) => !s)}
              className="absolute right-0 top-0 h-10 w-10 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-amber-500"
              aria-label={show ? 'Hide password' : 'Show password'}
              tabIndex={-1}
            >
              {show ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          )}
        </div>
        {error && (
          <p className="text-xs text-red-500 mt-1">{error}</p>
        )}
      </div>
    );
  }
);
Input.displayName = 'Input';