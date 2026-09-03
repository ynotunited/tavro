import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

/**
 * Proxy (Next 16 — formerly middleware).
 *
 * Hides the internal admin page tree /panel/* from direct access and exposes
 * it only through the non-guessable external path /<ADMIN_PANEL_PATH>/*.
 *
 * Why pages are mapped here (not via next.config rewrites): this Next version
 * re-runs the proxy on a next.config rewrite destination, so a rewrite into
 * /panel/* gets re-intercepted here and 404'd. Doing the page mapping in the
 * proxy itself (and tagging the rewrite with ?__admin so a re-run passes it
 * through) avoids that loop.
 *
 * Real access control is the backend 'admin' guard + login throttling + audit
 * trail; the obfuscated path/rewrite here is defense-in-depth only.
 */

const PANEL_INTERNAL = '/panel';
const PANEL_EXTERNAL = '/' + (process.env.NEXT_PUBLIC_ADMIN_PANEL_PATH || 'control-room-9f2k');

/** Marker query param applied to proxy-produced page rewrites. */
const MARK = '__admin';

export function proxy(request: NextRequest) {
  const { pathname, searchParams } = request.nextUrl;
  const marked = searchParams.get(MARK) === '1';

  // Map the public admin path to the internal page tree. The ?__admin marker
  // lets any proxy re-run on the destination pass through.
  if (!pathname.startsWith(PANEL_EXTERNAL + '/api') &&
      (pathname === PANEL_EXTERNAL || pathname.startsWith(PANEL_EXTERNAL + '/'))) {
    const rest = pathname.slice(PANEL_EXTERNAL.length) || '/';
    const q = rest.includes('?') ? `&${MARK}=1` : `?${MARK}=1`;
    const target = new URL(PANEL_INTERNAL + rest + q, request.url);
    return NextResponse.rewrite(target);
  }

  // Direct access to the internal /panel tree is not allowed.
  if (pathname === PANEL_INTERNAL || pathname.startsWith(PANEL_INTERNAL + '/')) {
    if (marked) {
      return NextResponse.next();
    }
    return NextResponse.rewrite(new URL('/404', request.url));
  }

  return NextResponse.next();
}

// Run on everything so the guard is always applied; cheap to evaluate.
export const config = {
  matcher: ['/:path*'],
};
