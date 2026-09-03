#!/usr/bin/env bash
# =============================================================================
# 01-ssh-hardening.sh
#
# SSH hardening for Ubuntu 22.04 / 24.04 VPS.
#
# What this does:
#   - Creates a non-root sudo user (idempotent).
#   - Installs your public key for that user (required).
#   - Updates sshd_config to: key-only auth, no root login, no passwords,
#     optional custom port, no empty passwords, longer login grace.
#   - Backs up sshd_config before touching it.
#   - Validates sshd config and masks the restart so you never get locked out.
#
# SAFETY: this script NEVER restarts sshd on its own. It prints the exact
# command to reload, which you run in a SECOND terminal after verifying you can
# log in as the new user. See the README.
#
# Usage:
#   sudo bash 01-ssh-hardening.sh [--port 2222] [--user deploy] [--pubkey "ssh-ed25519 AAAA..."]
#
# You can also set the same values via environment variables before running:
#   SSHD_PORT / SSHD_USER / SSHD_PUBKEY
# =============================================================================
set -euo pipefail

# ---- Config ---------------------------------------------------------------
NEW_USER="${SSHD_USER:-deploy}"
SSH_PORT="${SSHD_PORT:-22}"
PUBKEY="${SSHD_PUBKEY:-}"

CRED="\033[1;36m"; WARN="\033[1;33m"; GRN="\033[1;32m"; RED="\033[1;31m"; NC="\033[0m"
say(){ printf "${CRED}==>${NC} %b\n" "$1"; }
ok(){ printf "${GRN}ok:${NC} %b\n" "$1"; }
warn(){ printf "${WARN}warn:${NC} %b\n" "$1"; }
die(){ printf "${RED}error:${NC} %b\n" "$1" >&2; exit 1; }

# ---- Argument parsing -----------------------------------------------------
while [[ $# -gt 0 ]]; do
  case "$1" in
    --port)   SSH_PORT="$2"; shift 2 ;;
    --user)   NEW_USER="$2"; shift 2 ;;
    --pubkey) PUBKEY="$2";   shift 2 ;;
    *) die "unknown argument: $1" ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then die "run as root: sudo bash $0"; fi
case "$SSH_PORT" in
  ''|*[!0-9]*) die "--port must be a number (got '$SSH_PORT')" ;;
esac
if (( SSH_PORT < 1 || SSH_PORT > 65535 )); then die "--port out of range"; fi
if [[ -z "$PUBKEY" ]]; then
  die "no public key provided. Run: sudo bash $0 --pubkey \"ssh-ed25519 AAAA...yourkey\""
fi
# Validate it looks like a public key (accepts ssh-ed25519/rsa/ecdsa and sk-*)
if ! grep -Eq '^(ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp256|sk-ssh-ed25519|sk-ecdsa-sha2-nistp256) ' <<<"$PUBKEY"; then
  die "does not look like a public key: $PUBKEY"
fi

say "Targets: user='$NEW_USER' ssh_port='$SSH_PORT'"

# ---- OS / tool sanity -----------------------------------------------------
. /etc/os-release
if [[ "${ID:-}" != "ubuntu" ]]; then warn "not Ubuntu (got '${ID:-}') — review before continuing"; fi
command -v ufw >/dev/null 2>&1 || { say "installing ufw (needed by 02-firewall.sh)"; apt-get update -qq && apt-get install -y -qq ufw; }

# ---- Create non-root sudo user --------------------------------------------
if id "$NEW_USER" >/dev/null 2>&1; then
  ok "user '$NEW_USER' already exists"
else
  useradd -m -s /bin/bash "$NEW_USER"
  echo "$NEW_USER ALL=(ALL) NOPASSWD:ALL" > "/etc/sudoers.d/90-$NEW_USER"
  chmod 440 "/etc/sudoers.d/90-$NEW_USER"
  ok "created sudo user '$NEW_USER'"
fi

# ---- Install public key ---------------------------------------------------
AUTH="/home/$NEW_USER/.ssh/authorized_keys"
install -d -m 700 -o "$NEW_USER" -g "$NEW_USER" "/home/$NEW_USER/.ssh"
touch "$AUTH"
if grep -qF "$PUBKEY" "$AUTH"; then
  ok "public key already present for '$NEW_USER'"
else
  printf '%s\n' "$PUBKEY" >> "$AUTH"
  ok "public key added for '$NEW_USER'"
fi
chown "$NEW_USER:$NEW_USER" "$AUTH"
chmod 600 "$AUTH"

# ---- Backup sshd_config ---------------------------------------------------
SSHD="/etc/ssh/sshd_config"
BACKUP="${SSHD}.bak.$(date +%Y%m%d%H%M%S)"
cp -a "$SSHD" "$BACKUP"
ok "backed up sshd_config -> $BACKUP"

# ---- Apply hardening directives ------------------------------------------
# Use a drop-in include if supported, else append to the main file.
DROP_DIR="/etc/ssh/sshd_config.d"
DROP_FILE="$DROP_DIR/99-tavro-hardening.conf"

hardening_page() {
  cat <<EOF
# Managed by ops/security/01-ssh-hardening.sh — do not edit by hand
Port $SSH_PORT
PermitRootLogin no
PubkeyAuthentication yes
PasswordAuthentication no
KbdInteractiveAuthentication no
ChallengeResponseAuthentication no
UsePAM no
PermitEmptyPasswords no
MaxAuthTries 4
LoginGraceTime 45
X11Forwarding no
AllowAgentForwarding no
AllowTcpForwarding no
EOF
}

if [[ -d "$DROP_DIR" ]] && grep -q '^Include.*/sshd_config.d/\*\.conf' "$SSHD"; then
  hardening_page > "$DROP_FILE"
  chmod 600 "$DROP_FILE"
  ok "wrote drop-in -> $DROP_FILE"
else
  warn "no sshd_config.d include support; appending directly to $SSHD"
  printf '\n# Managed by ops/security/01-ssh-hardening.sh\n' >> "$SSHD"
  hardening_page >> "$SSHD"
fi

# ---- Validate config (do not restart) -------------------------------------
if sshd -t >/dev/null 2>&1; then
  ok "sshd_config is valid"
else
  say "rolling back (invalid config)"
  cp -a "$BACKUP" "$SSHD"
  sshd -t >/dev/null 2>&1 && ok "rolled back to last-known-good config" || die "config broken; restore $BACKUP manually"
fi

printf "\n"
say "Done. Before reloading sshd, open a SECOND terminal and verify:"
printf "   ssh -p %s %s@<server-ip>\n" "$SSH_PORT" "$NEW_USER"
printf "Only when that works, reload sshd from the FIRST terminal:\n"
printf "   sudo systemctl reload sshd\n"
printf "\n"
if [[ "$SSH_PORT" != "22" ]]; then
  warn "You changed the SSH port to $SSH_PORT — make sure 02-firewall.sh allows it, or you will lock yourself out."
fi
