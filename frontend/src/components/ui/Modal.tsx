'use client';
import React, { useEffect, useSyncExternalStore } from 'react';
import { createPortal } from 'react-dom';

export interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
}

const emptySubscribe = () => () => {};

export function Modal({ isOpen, onClose, title, children }: ModalProps) {
  // Hydration guard: on the server this is false (no portal to document.body),
  // on the client it flips to true after hydration. Using useSyncExternalStore
  // avoids setState inside an effect (react-hooks/set-state-in-effect).
  const mounted = useSyncExternalStore(emptySubscribe, () => true, () => false);

  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, [isOpen]);

  if (!mounted || !isOpen) return null;

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div 
        className="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
        onClick={onClose}
      />
      
      <div className="relative z-50 w-full max-w-lg bg-surface border border-border shadow-lg p-6 rounded-none animate-in fade-in zoom-in-95 duration-200">
        {title && (
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-primary">{title}</h2>
            <button 
              onClick={onClose}
              className="text-muted hover:text-primary transition-colors"
            >
              ✕
            </button>
          </div>
        )}
        
        <div>{children}</div>
      </div>
    </div>,
    document.body
  );
}
