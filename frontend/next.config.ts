import { withSentryConfig } from "@sentry/nextjs";
import type { NextConfig } from "next";

// next-pwa ships no type declarations; CJS require is required here.
// eslint-disable-next-line @typescript-eslint/no-require-imports
const withPWA = require('next-pwa')({
  dest: 'public',
  disable: process.env.NODE_ENV === 'development',
  register: true,
  skipWaiting: true,
});

const nextConfig: NextConfig = {
  turbopack: {},
  async rewrites() {
    const panel = process.env.NEXT_PUBLIC_ADMIN_PANEL_PATH || 'control-room-9f2k';
    // Backend origin used for the admin API proxy. Keep in sync with the
    // backend's own URL (defaults match the Laravel `artisan serve` dev server).
    const backend = process.env.NEXT_PUBLIC_ADMIN_BACKEND_URL || 'http://localhost:8000';
    return [
      {
        // Admin API — same-origin proxy to the backend session backend so the
        // admin session cookie (SameSite=lax) works across the ports. The
        // admin UI *pages* are mapped at the edge in src/proxy.ts (external
        // /<panel>/* → internal /panel/*), because Next rewrites into /panel/*
        // get re-intercepted by the proxy in this Next version.
        source: `/${panel}/api/:path*`,
        destination: `${backend}/${panel}/:path*`,
      },
    ];
  },
};

const sentryBuildOptions = {
  // Read from env (SENTRY_ORG / SENTRY_PROJECT / SENTRY_AUTH_TOKEN).
  // When the auth token is absent the plugin only warns and skips source-map
  // upload, so builds still succeed in local/dev.
  org: process.env.SENTRY_ORG,
  project: process.env.SENTRY_PROJECT,
  authToken: process.env.SENTRY_AUTH_TOKEN,
  silent: true,
  telemetry: false,
  sourcemaps: {
    disable: !process.env.SENTRY_AUTH_TOKEN,
  },
};

// Sentry runtime wiring happens in src/instrumentation.ts (server/edge) and
// src/instrumentation-client.ts (browser) — withSentryConfig handles release
// injection + route-handler/server-component instrumentation.
export default withSentryConfig(withPWA(nextConfig), sentryBuildOptions);