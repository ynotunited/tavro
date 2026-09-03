import Image from 'next/image';

interface LogoProps {
  /** 'dark' is the standard logo (dark wordmark, for light backgrounds); 'white' is for charcoal/light-on-dark surfaces. */
  variant?: 'dark' | 'white';
  /** Horizontal size in px of the full-width wordmark (height auto-scales by aspect ratio). */
  width?: number;
  className?: string;
  priority?: boolean;
}

/**
 * Tavro brand logo.
 *
 * src/app favicon + PWA icons are wired separately (src/app/icon.png); this is
 * the visible wordmark used in headers, auth screens and sidebars.
 */
export default function Logo({ variant = 'dark', width = 180, className = '', priority = false }: LogoProps) {
  const src = variant === 'white' ? '/tavro_logo-app-white.png' : '/tavro_logo-app.png';

  return (
    <Image
      src={src}
      alt="Tavro"
      width={width}
      height={Math.round((width * 182) / 656)}
      priority={priority}
      className={className}
      style={{ height: 'auto', width: 'auto', maxWidth: '100%' }}
    />
  );
}
