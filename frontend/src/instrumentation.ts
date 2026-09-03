import * as Sentry from '@sentry/nextjs';

/**
 * Server & Edge runtime Sentry initialization.
 *
 * Next.js runs this file's `register()` once per server instance. The SDK's
 * webpack bundler resolves `@sentry/nextjs` to the node or edge bundle based on
 * `NEXT_RUNTIME`, so a single init call covers both runtimes.
 *
 * This replaces the deprecated `sentry.server.config.ts` / `sentry.edge.config.ts`
 * files (removed — see warnings in the SDK under Turbopack).
 */
export async function register() {
  const dsn =
    process.env.SENTRY_DSN ||
    process.env.NEXT_PUBLIC_SENTRY_DSN ||
    '';

  if (!dsn) {
    return;
  }

  Sentry.init({
    dsn,
    environment: process.env.NEXT_PUBLIC_APP_ENV || process.env.NODE_ENV,
    tracesSampleRate: Number(process.env.SENTRY_TRACES_SAMPLE_RATE ?? 1),
    profilesSampleRate: Number(process.env.SENTRY_PROFILES_SAMPLE_RATE ?? 1),
    debug: false,
  });
}