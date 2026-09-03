import * as Sentry from '@sentry/nextjs';
import { initRageClickDetection } from './lib/rageClicks';
import { stampReplayId, syncSentryAuthContext } from './lib/sentry';

/**
 * Browser Sentry initialization + Session Replay.
 *
 * Runs after the HTML document loads and before React hydration (Next.js
 * `instrumentation-client` convention) — the right moment to attach global
 * listeners and seed error/replay sampling. Only synchronous top-level work is
 * guaranteed before hydration, so everything here is synchronous inside
 * try/catch and dynamic work (replay buffers, sampling) is owned by the SDK.
 *
 * This replaces the deprecated `sentry.client.config.ts`.
 */

try {
  const dsn = process.env.NEXT_PUBLIC_SENTRY_DSN || '';

  if (dsn) {
    Sentry.init({
      dsn,
      environment: process.env.NEXT_PUBLIC_APP_ENV || process.env.NODE_ENV,
      tracesSampleRate: Number(process.env.NEXT_PUBLIC_SENTRY_TRACES_SAMPLE_RATE ?? 1),
      // Session-level replay sampling (random sessions recorded) vs
      // error-driven sampling (replay attached whenever an error fires).
      replaysSessionSampleRate: Number(
        process.env.NEXT_PUBLIC_SENTRY_REPLAYS_SESSION_SAMPLE_RATE ?? 0
      ),
      replaysOnErrorSampleRate: Number(
        process.env.NEXT_PUBLIC_SENTRY_REPLAYS_ON_ERROR_SAMPLE_RATE ?? 1
      ),
      integrations: [
        Sentry.replayIntegration({
          maskAllText: true,
          maskAllInputs: true,
          blockAllMedia: true,
          // Flag interactions that take >4s to respond as slow clicks.
          slowClickTimeout: 4000,
          networkDetailAllowUrls: [window.location.origin],
          networkRequestHeaders: ['Referer'],
          networkResponseHeaders: [],
        }),
      ],
      // Explicitly connect every captured event to the active replay.
      beforeSend: stampReplayId,
    });
  }

  syncSentryAuthContext();
  initRageClickDetection();
} catch {
  // Instrumentation must never break app startup.
}

// Navigation tracing + breadcrumbs (required by the Sentry SDK — see its
// "ACTION REQUIRED" warning if this hook is missing).
export const onRouterTransitionStart = Sentry.captureRouterTransitionStart;