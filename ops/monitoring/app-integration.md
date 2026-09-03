# Tavro App — Error Tracking & Monitoring Integration

This explains the **in-app** side of monitoring: how exceptions surface, how to
finish wiring error tracking (Sentry), and how the built-in monitoring/issue
endpoints are used by the dev company.

## What's already built into the app

Several monitoring features already exist in the Laravel backend:

| Capability | Where |
|------------|-------|
| Per-request metrics (status, latency, errors) | `RequestAnalytics` — written by `RecordAnalytics` middleware, served by `AnalyticsController` (`/analytics/*`) |
| Slow-request + high-error-rate logs | `RecordAnalytics` → `security` log channel |
| Customer issue reporting (user → dev) | `IssueController` `/issues` — category/severity/status workflow |
| Auto-issue from critical exceptions | `bootstrap/app.php` creates an `Issue` on `PDOException`/`ErrorException` |
| Unhandled-exception logging | `bootstrap/app.php` → `security` channel |
| Laravel health endpoint | `GET /up` (already registered, returns 200) |
| Cross-tenant ops summary | **NEW** `OpsMonitorController` `/ops/*` (this work) |

## NEW internal ops endpoints

Protected by an internal token (`config('security.ops_token')` → `OPS_TOKEN`),
NOT a tenant credential. Use a bearer token:

```
GET    /api/v1/ops/summary          # one-shot health + incident snapshot
GET    /api/v1/ops/errors           # error rate per endpoint (last 60 min)
GET    /api/v1/ops/issues?status=   # cross-tenant open issues
POST   /api/v1/ops/issues/{id}/resolve  # resolve an issue on customer's behalf
```

Example:

```bash
curl -H "Authorization: Bearer $OPS_TOKEN" \
     -A "TavroOpsProbe/1.0" \
     https://api.tavro.ng/api/v1/ops/summary
```

Notes:
- Set `OPS_TOKEN` to a long random string in the backend `.env`.
- The app's own security layer **blocks the `curl/` user agent** (403). Any
  script must send a browser-like or custom UA (above uses `TavroOpsProbe/1.0`).
- These endpoints are rate-limited (`throttle:ops`) and read-only except the
  explicit `resolve`.

### How users report problems (the live support channel)

Users can already file an issue in the app via `POST /api/v1/issues`. The dev
team sees those across all tenants via `/ops/issues` / `ops/summary`. On a real
deployment, alert on `ops.summary.issues.urgent` / `error_rate_24h`.

## Wiring Sentry (error tracking)

The packages are **already installed and registered** — only the DSN is missing.

### Backend (Laravel) — `sentry/sentry-laravel`, already active
1. Get a DSN. Two options:
   - **Sentry cloud** → create an org/project, copy the DSN.
   - **Self-hosted GlitchTip** (Sentry-compatible) → see the monitoring compose.
2. Set in `backend/.env`:
   ```env
   SENTRY_LARAVEL_DSN=https://<public-key>@<host>/<id>
   SENTRY_ENVIRONMENT=production
   SENTRY_RELEASE=<git short sha>
   ```
3. Clear caches so it takes effect:
   ```bash
   php artisan config:clear
   ```
4. Exceptions already flow via `Sentry\Laravel\Integration::handles()` in
   `bootstrap/app.php`. No code change needed.

### Frontend (Next.js) — `@sentry/nextjs` installed but NOT initialized

1. Add the DSN to `frontend/.env.local`:
   ```env
   NEXT_PUBLIC_SENTRY_DSN=https://<public-key>@<host>/<id>
   ```
   (For GlitchTip point this at the same GlitchTip host.)
2. Create `frontend/instrumentation.ts` (Next.js auto-loads it) to init the SDK.
   Because you are using the app router, also create
   `sentry.client.config.ts` and `sentry.server.config.ts` or rely on
   `instrumentation.ts`. A minimal client init:

   ```ts
   // frontend/sentry.client.config.ts
   import * as Sentry from "@sentry/nextjs";

   Sentry.init({
     dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
     tracesSampleRate: 0.2,
   });
   ```

   ```ts
   // frontend/instrumentation.ts
   export async function register() {
     if (process.env.NEXT_RUNTIME === "nodejs") {
       const { init } = await import("@sentry/nextjs");
       init({ dsn: process.env.NEXT_PUBLIC_SENTRY_DSN, tracesSampleRate: 0.2 });
     }
   }
   ```

   Then wrap the root layout or use
   `Sentry.captureException(e)` in client error boundaries / the global
   `unhandledrejection` handler where you see fit.

3. Rebuild/restart the frontend.

### Which tool for which job (recommendation)
- **Uptime** and **alerting** → Uptime Kuma (monitors `/up`, frontend, and the
  `/ops/summary` response).
- **Exceptions/stack traces** → Sentry (or GlitchTip). This is the "one place
  for every error" the backend already pushes towards.
- **OS/load metrics** → Prometheus + node_exporter + Grafana.
- **Business/customer signals** → the built-in `/ops/*` endpoints.

See `README.md` for the full dev-team runbook and the self-hosted stack.
