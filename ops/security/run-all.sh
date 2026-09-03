#!/usr/bin/env bash
# =============================================================================
# run-all.sh
#
# Runs the full VPS hardening suite in safe order:
#   01 ssh hardening  -> 02 firewall  -> 03 auto security updates
#
# IMPORTANT: SSH hardening does NOT restart sshd itself — this script stops
# there and prints the reload instruction, because reloading sshd before you
# have verified the new non-root login is the classic way to lock yourself out.
#
# Usage:
#   sudo bash run-all.sh <server-user> <public-key> [--ssh-port 2222] [--ports "80,443"]
#
# Example:
#   sudo bash run-all.sh deploy "ssh-ed25519 AAAA..." --ssh-port 2222 --ports "80,443,8080"
# =============================================================================
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then echo "run as root: sudo bash $0" >&2; exit 1; fi

USER_NAME="${1:-}"
PUBKEY="${2:-}"
shift 2 || true

if [[ -z "$USER_NAME" || -z "$PUBKEY" ]]; then
  echo "usage: sudo bash $0 <user> <public-key> [--ssh-port N] [--ports \"a,b\"]" >&2
  exit 1
fi

SSH_PORT="22"
PORTS="80,443"
while [[ $# -gt 0 ]]; do
  case "$1" in
    --ssh-port) SSH_PORT="$2"; shift 2 ;;
    --ports)    PORTS="$2";    shift 2 ;;
    *) echo "unknown argument: $1" >&2; exit 1 ;;
  esac
done

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> STEP 1/3: SSH hardening"
bash "$DIR/01-ssh-hardening.sh" --user "$USER_NAME" --pubkey "$PUBKEY" --port "$SSH_PORT"

echo "==> STEP 2/3: Firewall (SSH $SSH_PORT, allowlist [$PORTS])"
bash "$DIR/02-firewall.sh" --ssh-port "$SSH_PORT" --ports "$PORTS"

echo "==> STEP 3/3: Automatic security updates"
bash "$DIR/03-auto-security-updates.sh"

echo ""
echo "run-all complete (except intentionally leaving sshd un-reloaded)."
echo "Open a SECOND terminal and confirm you can log in as '$USER_NAME' on port $SSH_PORT,"
echo "then from the first terminal run:  sudo systemctl reload sshd"
