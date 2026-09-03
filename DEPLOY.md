# Tavro — Deployment Runbook (Vercel + Railway)

Tavro is a monorepo:
- `backend/`  — Laravel 11 + PostgreSQL + Laravel Reverb (WebSocket realtime)
- `frontend/` — Next.js 16 (Turbopack) app

Deployment targets:
- **Frontend** → Vercel (Hobby)
- **Backend API + PostgreSQL + Reverb** → Railway (Docker)

Realtime works across devices via **Laravel Reverb**. The backend already
broadcasts `private-branch.{id}.kitchen / .bar / .tables` events and the
frontend authorizes those private channels by POSTing to
`POST /api/v1/broadcasting/auth` (this route was added so authorization no
longer 404s).

---

## 1. Backend on Railway

### Create the services

Railway has one project with three services, each pointing at `backend/`:

| Service | Root Directory | Build | Start (PROCESS_TYPE) | Public networking |
|---------|----------------|-------|----------------------|-------------------|
| `db` (PostgreSQL) | — | Railway PostgreSQL plugin | — | Internal only |
| `api` | `backend` | Dockerfile (default) | *(none — defaults to web)* | Public TCP `$PORT` → HTTPS |
| `reverb` | `backend` | Same Dockerfile | `reverb` | Public TCP `$PORT` → WSS |

Steps:
1. Add a **PostgreSQL** plugin service (Railway gives you a `DATABASE_URL` env
   automatically, e.g. `postgresql://user:pass@host:5432/railway`). For the
   app service, map it to the expected vars (see below).
2. New service → deploy from GitHub repo → Root Directory = `backend`,
   build = **Dockerfile**. Railway builds `backend/Dockerfile`.
3. Configure a second service from the same repo/root directory, set its env
   `PROCESS_TYPE=reverb`, and expose its `$PORT` publicly. Railway's proxy turns
   that `$PORT` into HTTPS / WSS (wss on the public domain).

### Env vars for the `api` service

```env
APP_NAME=Tavro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<api-domain>.up.railway.app          # e.g. https://tavro-api.up.railway.app
APP_KEY=<paste from: php artisan key:generate --show>

# PostgreSQL (from the db plugin or your own instance)
DB_CONNECTION=pgsql
DB_HOST=<railway postgres host>                        # e.g. caboose.proxy.rlwy.net
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=<user>
DB_PASSWORD=<password>

# Realtime — connector + the WebSocket public endpoint clients will use
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=tavro-key
REVERB_APP_SECRET=<long random string, share with frontend app id/port below>
REVERB_APP_ID=tavro-app
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=<reverb public domain>                     # e.g. tavro-reverb.up.railway.app (no scheme)
REVERB_PORT=443                                        # port the BROWSER connects to
REVERB_SCHEME=https

# CORS / allowed origin for the private channel (the Vercel frontend domain)
FRONTEND_URL=https://<your-app>.vercel.app

# Keep everything database-backed so no Redis is required for the demo
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Any existing vars your app reads (TELEGRAM_*, OPS_TOKEN, ADMIN_PANEL_PATH, etc.)
```

> Routes are `/api/v1/*`, and the authenticated API group also requires the
> HMAC `signature.verify` middleware for normal calls. The new
> `POST /broadcasting/auth` route is `auth:sanctum` only (no HMAC), on
> purpose.

### Env vars for the `reverb` service

Same core set as above (DB + `BROADCAST_CONNECTION=reverb`), plus:

```env
PROCESS_TYPE=reverb
REVERB_SERVER_PORT=8080
REVERB_HOST=<reverb public domain>
REVERB_PORT=443
REVERB_SCHEME=https
```

Repeating `REVERB_APP_KEY/SECRET/APP_ID` exactly (they must match the `api`
service, because the same `config/reverb.php` credentials gate the WSS
connection).

---

## 2. Frontend on Vercel

Import `frontend/` as a project (Root Directory = `frontend`, Vercel auto-detects
Next.js). Set these env vars:

```env
# Full API base URL including the /api/v1 prefix
NEXT_PUBLIC_API_URL=https://<api-domain>.up.railway.app/api/v1

# Reverb WebSocket endpoint (client-side connection)
NEXT_PUBLIC_REVERB_APP_KEY=tavro-key
NEXT_PUBLIC_REVERB_HOST=<reverb public domain>
NEXT_PUBLIC_REVERB_PORT=443
NEXT_PUBLIC_REVERB_SCHEME=https
```

These must match the backend: `NEXT_PUBLIC_REVERB_APP_KEY` == `REVERB_APP_KEY`,
and `HOST/PORT/SCHEME` must point at the public Reverb domain over wss.

### How the pieces connect

- Frontend opens `wss://<reverb-domain>:443/app/<REVERB_APP_KEY>`.
- To subscribe to a private channel, the browser POSTs
  `{ socket_id, channel_name }` to `<NEXT_PUBLIC_API_URL>/broadcasting/auth`
  (i.e. `https://<api-domain>/api/v1/broadcasting/auth`) — the route added in
  this work. It returns a Reverb `{ auth: "<key>:<sig>" }` payload.
- Reverb then allows the socket only if `Branch`/user ownership matches
  (`routes/channels.php`), and backend events (`KitchenTicketUpdated` etc.,
  `ShouldBroadcastNow`) flow to all connected clients on the same branch.

---

## 3. First-time data (one-time, on Railway)

From the `api` service → **Run command** (Railway console), or `railway run`:

```bash
# generates the APP_KEY to paste into env if you don't have one
php artisan key:generate --show

# DB schema
php artisan migrate --force

# Demo org + user accounts (owner@demo.tavro.ng / password, waiter@demo.tavro.ng / password)
php artisan db:seed --class=DatabaseSeeder

# ~328 days of sales history for real-looking reports
php artisan db:seed --class=DemoOrdersSeeder
```

On every deploy the Dockerfile entrypoint auto-runs `php artisan migrate --force`
(disable with `SKIP_MIGRATIONS=1`).

---

## 4. Verify

1. Load the Vercel frontend, log in as `owner@demo.tavro.ng` / `password`.
2. Open the Kitchen and Bar pages in two browser windows.
3. Place/advance an order in one window → the other updates live.
4. Backend sanity: `POST https://<api-domain>/api/v1/broadcasting/auth` with a
   bearer token and `channel_name=private-branch.2.kitchen` returns 200 + `auth`.
