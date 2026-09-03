#!/usr/bin/env bash
# =============================================================================
# 02-firewall.sh
#
# UFW firewall for Ubuntu 22.04 / 24.04 — DENY EVERYTHING by default, allow
# only what you explicitly list.
#
# Safe-by-default ordering:
#   1. Set ufw to default deny inbound / allow outbound.
#   2. ALWAYS open SSH *first* on the chosen port (never locks you out).
#   3. Then open the explicit allowlist ports.
#   4. Enable ufw last.
#
# This is idempotent: re-running just re-applies the allowlist.
#
# Usage:
#   sudo bash 02-firewall.sh [--ssh-port 2222] [--ports "80,443" --ports "8080"]
#   Env: SSH_PORT / ALLOW_PORTS (comma-separated)
# =============================================================================
set -euo pipefail

SSH_PORT="${SSH_PORT:-22}"
ALLOW_PORTS="${ALLOW_PORTS:-80,443}"   # default: web (HTTP/HTTPS)

CRED="\033[1;36m"; WARN="\033[1;33m"; GRN="\033[1;32m"; RED="\033[1;31m"; NC="\033[0m"
say(){ printf "${CRED}==>${NC} %b\n" "$1"; }
ok(){ printf "${GRN}ok:${NC} %b\n" "$1"; }
warn(){ printf "${WARN}warn:${NC} %b\n" "$1"; }
die(){ printf "${RED}error:${NC} %b\n" "$1" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --ssh-port) SSH_PORT="$2"; shift 2 ;;
    --ports)    ALLOW_PORTS="$2"; shift 2 ;;
    *) die "unknown argument: $1" ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then die "run as root: sudo bash $0"; fi

# Normalise allowlist: split "80 443,8080" / "80,443" into unique ints.
PORTS=$(tr ',' ' ' <<<"$ALLOW_PORTS" | xargs -n1 | sort -un | xargs)

say "SSH port: $SSH_PORT | allowlist ports: ${PORTS:-<none>}"

# ---- Default policy: deny everything not explicitly allowed --------------
ufw default deny incoming
ufw default allow outgoing
ok "default policy set: deny incoming, allow outgoing"

# ---- SSH first ------------------------------------------------------------
if ufw status | grep -qE "^\s*$SSH_PORT/tcp\s+ALLOW"; then
  ok "SSH port $SSH_PORT already allowed"
else
  ufw allow "$SSH_PORT/tcp" comment 'SSH'
  ok "allowed SSH port $SSH_PORT/tcp"
fi

# ---- Explicit allowlist ---------------------------------------------------
if [[ -n "${PORTS:-}" ]]; then
  for port in $PORTS; do
    if ufw status | grep -qE "^\s*$port/tcp\s+ALLOW"; then
      ok "port $port/tcp already allowed"
    else
      ufw allow "$port/tcp" comment 'app allowlist'
      ok "allowed port $port/tcp"
    fi
  done
else
  warn "no extra ports allowed (HTTP/HTTPS left to the allowlist above)"
fi

# ---- Enable ---------------------------------------------------------------
ufw --force enable
ufw status verbose
say "Firewall enabled. Review the list above and add any other exposed ports with:"
printf "   sudo ufw allow <port>/tcp\n"
