# Tavro — Production Monitoring (dev company runbook)

How the development company watches Tavro in production and how customers reach
support. Current date: 2026-08-31.

## The big picture

Two complementary layers:

1. **In-app signals** (already in the Laravel backend)
   - Every request recorded (`RequestAnalytics`) → error rate, latency,
     slow requests, per-org dashboards.
   - Customer issues file into `/issues` (category/severity/status).
   - Unhandled exceptions auto-create an issue and go to a `security` log.
   - **NEW** cross-tenant ops endpoints (`/ops/*`) give the dev team a single
     read-only health + incident snapshot.

2. **Self-hosted ops stack** (in this folder) — uptime, error tracking, and
   OS metrics, all free / open-source.

```
┌───────────────────────── In-app ─────────────────────────┐
│  Tavro Laravel API                                      │
│   • RequestAnalytics  → per-org dashboards              │
│   • /issues           → customer → dev support channel  │
│   • /up               → liveness                        │
│   • /ops/*            → dev-team cross-tenant summary   │
└──────────────────────────────────────────────────────────┘
                │  (metrics / events)
                ▼
┌───────────────────────── Ops stack (Docker) ─────────────┐
│  Uptime Kuma   → probes /up, frontend, /ops → alert      │
│  GlitchTip     → error tracker (Sentry-compatible)        │
│  Prometheus    → scrapes node_exporter (OS metrics)       │
│  Grafana       → dashboards over Prometheus               │
└──────────────────────────────────────────────────────────┘
```

## Files

| File | Purpose |
|------|---------|
| `docker-compose.monitoring.yml` | Self-hosted Uptime Kuma + GlitchTip + Prometheus + node_exporter + Grafana |
| `prometheus.yml` | Scrape config for the compose stack |
| `health-check.sh` | Liveness probe (API/frontend/Reverb/DB/Redis) → JSON exit code |
| `app-integration.md` | Wiring Sentry + the built-in `/ops/*` endpoints + issue channel |
| `README.md` | This runbook |

Backend code added for monitoring:

- `app/Http/Controllers/OpsMonitorController.php`
- `app/Http/Middleware/VerifyOpsToken.php`
- `config/security.php` (`ops_token`)
- Routes under `/api/v1/ops` in `routes/api/v1.php`
- `OPS_TOKEN` in `backend/.env(.example)`

## 1. Set up the self-hosted ops stack

On a monitoring VM / the same VPS (Docker available):

```bash
cd ops/monitoring
# secrets:
cat > .monitoring.env <<'EOF'
GLITCHTIP_DB_PASSWORD=<long random>
GLITCHTIP_SECRET_KEY=<long random>
GRAFANA_ADMIN_PASSWORD=<strong>
EOF
docker compose -f docker-compose.monitoring.yml up -d
```

| Service | UI / port (on monitoring host) |
|---------|-------------------------------|
| Uptime Kuma | `http://<host>:3001` |
| GlitchTip | `http://<host>:3002` |
| Prometheus | `http://<host>:3003` |
| Grafana | `http://<host>:3004` |

> Harden these behind the firewall / SSH — do not expose ports 3001–3004
> publicly. Uptime Kuma's only role is outbound probing of the app.

## 2. Point Uptime Kuma at Tavro

Add monitors:
- **HTTP GET** → `https://api.tavro.ng/up` (expect 200)
- **HTTP GET** → `https://app.tavro.ng/` (expect 200)
- **HTTP GET** → `https://api.tavro.ng/api/v1/ops/summary` with header
  `Authorization: Bearer $OPS_TOKEN` (expect 200) — a full-stack health check.

Configure notifications (Email/Slack/Telegram) for DOWN events.

## 3. Wire error tracking (once)

Follow `app-integration.md`:
- Backend: put the DSN in `SENTRY_LARAVEL_DSN` (already fully wired).
- Frontend: init `@sentry/nextjs` (package present, not yet initialized).

Use **GlitchTip** for everything if you stay free/self-hosted, or **Sentry
cloud** if you want managed. Both accept the same SDK DSN format.

## 4. Load the Grafana dashboard

- Add Prometheus data source (`http://prometheus:9090`).
- Import a host dashboard using the `node` job (CPU/RAM/disk/network).

## 5. Run the CLI health probe (optional)

For cron/systemd or CI:

```bash
OPS_TOKEN=<same> API_URL=https://api.tavro.ng ./health-check.sh
echo "exit code: $?"   # 0 healthy, 1 degraded, 2 down
```

Schedule every minute (systemd timer or cron):

```cron
* * * * * /usr/local/bin/tavro-health.sh
```

## The support loop (how customers contact us)

1. A customer hits an in-app problem and files an issue
   (`POST /api/v1/issues` in the app UI, or it's auto-created by a critical
   exception).
2. Dev team sees it in `/ops/summary` (recent open issues) or `/ops/issues`.
3. Alert rules (e.g. on `ops.summary.issues.urgent > 0`) page the on-call dev.
4. Dev fixes; resolves the customer issue via
   `POST /api/v1/ops/issues/{id}/resolve`.
5. For critical incidents, the exception also landed in Sentry/GlitchTip with
   the full stack trace.

## Incident alert thresholds (suggested start)

- API `/up` down → critical (Uptime Kuma).
- `error_rate_24h > 5%` with `total_requests_24h >= 50` → page (from
  `/ops/summary`).
- Any `high`/`critical` open customer issue → notify.
- CPU sustained > 90% / disk > 85% → notify (Grafana alert).

## Security notes

- `OPS_TOKEN` is a dedicated secret, never reused as a customer key.
- The `security` log channel and request-signing layers stay intact for `/ops`.
- Monitoring ports are internal; keep them off the public internet.
