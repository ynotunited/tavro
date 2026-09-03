#!/usr/bin/env bash
# =============================================================================
# health-check.sh
#
# Lightweight liveness probe for the Tavro deployment. Designed to be run by
# an external scheduler (cron / systemd timer / Uptime Kuma "push" monitor).
#
# Exits 0 if healthy, 1 if degraded, 2 if down. Prints a single JSON line.
#
# Checks:
#   - Laravel API  /up                 (Laravel health endpoint + returns 200)
#   - Frontend     HTTP status         (the Next.js app)
#   - Reverb       /apps/<app-id>/...  (websocket health; needs auth — best
#                                       checked as a TCP connect on the port)
#   - PostgreSQL   pg_isready (if run on the DB host / with tools)
#   - Redis        redis-cli PING
#
# Usage:
#   ./health-check.sh [--api https://api.example.com] [--port 3306] ...
#   Env: API_URL, FRONTEND_URL, REVERB_HOST, REVERB_PORT, POSTGRES_HOST
#        POSTGRES_PORT, REDIS_HOST, REDIS_PORT
# =============================================================================
set -uo pipefail

API_URL="${API_URL:-http://localhost:8000}"
FRONTEND_URL="${FRONTEND_URL:-http://localhost:3000}"
REVERB_HOST="${REVERB_HOST:-127.0.0.1}"
REVERB_PORT="${REVERB_PORT:-8080}"
POSTGRES_HOST="${POSTGRES_HOST:-127.0.0.1}"
POSTGRES_PORT="${POSTGRES_PORT:-5432}"
PGUSER="${PGUSER:-postgres}"
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
REDIS_PORT="${REDIS_PORT:-6379}"

declare -A RESULTS
STATUS_CODE=0

probe_http(){ # $1 name, $2 url, $3 expect_string? optional
  local name="$1" url="$2" want="${3:-}" ua
  ua="TavroHealthCheck/1.0 (ops)"
  local code out
  out=$(curl -sS -m 10 -A "$ua" -o /tmp/hc_body_$(basename "$0").$$ -w '%{http_code}' "$url" 2>&1)
  code=$?
  local body=""; [[ -f /tmp/hc_body_$(basename "$0").$$ ]] && body=$(cat /tmp/hc_body_$(basename "$0").$$); rm -f /tmp/hc_body_$(basename "$0").$$
  if [[ "$code" -ne 0 || "$out" == "000" ]]; then
    RESULTS["$name"]="DOWN"; STATUS_CODE=2; return
  fi
  if [[ -n "$want" ]] && ! grep -qF "$want" "$body" 2>/dev/null; then
    RESULTS["$name"]="DEGRADED (missing '$want')"; [[ $STATUS_CODE -lt 1 ]] && STATUS_CODE=1; return
  fi
  [[ "$out" -ge 500 ]] && { RESULTS["$name"]="DOWN (HTTP $out)"; STATUS_CODE=2; return; }
  [[ "$out" -ge 400 ]] && { RESULTS["$name"]="DEGRADED (HTTP $out)"; [[ $STATUS_CODE -lt 1 ]] && STATUS_CODE=1; return; }
  RESULTS["$name"]="UP (HTTP $out)"
}

probe_tcp(){ # $1 name, $2 host, $3 port
  local name="$1" host="$2" port="$3"
  if timeout 5 bash -c "exec 3<>/dev/tcp/$host/$port" 2>/dev/null; then
    RESULTS["$name"]="UP"
  else
    RESULTS["$name"]="DOWN"; STATUS_CODE=2
  fi
  exec 3>&- 2>/dev/null
}

# ---- API health -----------------------------------------------------------
probe_http "api.up" "$API_URL/up"

# ---- Frontend -------------------------------------------------------------
probe_http "frontend" "$FRONTEND_URL/"

# ---- Reverb (websocket) : TCP connect to the port -------------------------
probe_tcp "reverb" "$REVERB_HOST" "$REVERB_PORT"

# ---- PostgreSQL -----------------------------------------------------------
if command -v pg_isready >/dev/null 2>&1; then
  pg_isready -h "$POSTGRES_HOST" -p "$POSTGRES_PORT" -U "$PGUSER" -q >/dev/null 2>&1 \
    && RESULTS["postgres"]="UP" || { RESULTS["postgres"]="DOWN"; STATUS_CODE=2; }
else
  probe_tcp "postgres.tcp" "$POSTGRES_HOST" "$POSTGRES_PORT"
fi

# ---- Redis ----------------------------------------------------------------
if command -v redis-cli >/dev/null 2>&1; then
  pong=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping 2>/dev/null)
  [[ "$pong" == "PONG" ]] && RESULTS["redis"]="UP" || { RESULTS["redis"]="DOWN"; STATUS_CODE=2; }
else
  probe_tcp "redis.tcp" "$REDIS_HOST" "$REDIS_PORT"
fi

# ---- Emit JSON ------------------------------------------------------------
ts=$(date -u +%Y-%m-%dT%H:%M:%SZ)
json="{ \"ts\": \"$ts\", \"healthy\": $( [[ $STATUS_CODE == 0 ]] && echo true || echo false ),"
sep=""
for k in "${!RESULTS[@]}"; do
  json+="${sep}\"$k\": \"${RESULTS[$k]}\""; sep=", "
done
json+=" }"
printf '%s\n' "$json"
exit $STATUS_CODE
