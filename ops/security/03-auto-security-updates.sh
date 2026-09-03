#!/usr/bin/env bash
# =============================================================================
# 03-auto-security-updates.sh
#
# Configure unattended-upgrades for Ubuntu 22.04 / 24.04 so security updates
# install automatically, with:
#   - security updates:      auto-install (default)
#   - all other updates:     download only (no forced install)
#   - automatic reboot:      only when required for a security update
#   - email notifications:   off by default (set AUTOUPG_EMAIL to enable)
#   - randomized delay:      avoids all VPS rebooting at once
#
# Idempotent.
#
# Usage:
#   sudo bash 03-auto-security-updates.sh [--email admin@example.com]
#   Env: AUTOUPG_EMAIL / AUTOUPG_REBOOT (true|false)
# =============================================================================
set -euo pipefail

EMAIL="${AUTOUPG_EMAIL:-}"
REBOOT="${AUTOUPG_REBOOT:-true}"   # only reboot when security update requires it

CRED="\033[1;36m"; WARN="\033[1;33m"; GRN="\033[1;32m"; RED="\033[1;31m"; NC="\033[0m"
say(){ printf "${CRED}==>${NC} %b\n" "$1"; }
ok(){ printf "${GRN}ok:${NC} %b\n" "$1"; }
warn(){ printf "${WARN}warn:${NC} %b\n" "$1"; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --email) EMAIL="$2"; shift 2 ;;
    --reboot) REBOOT="$2"; shift 2 ;;
    *) echo "unknown argument: $1" >&2; exit 1 ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then echo "run as root: sudo bash $0" >&2; exit 1; fi

# ---- Install --------------------------------------------------------------
if dpkg -s unattended-upgrades >/dev/null 2>&1; then
  ok "unattended-upgrades already installed"
else
  apt-get update -qq
  DEBIAN_FRONTEND=noninteractive apt-get install -y -qq unattended-upgrades
  say "installed unattended-upgrades"
fi

# Auto-enable base config if present.
if [[ -f /etc/apt/apt.conf.d/20auto-upgrades ]]; then
  ok "20auto-upgrades already present"
else
  dpkg-reconfigure -plow unattended-upgrades || true
  if [[ ! -f /etc/apt/apt.conf.d/20auto-upgrades ]]; then
    cat > /etc/apt/apt.conf.d/20auto-upgrades <<'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
  fi
  ok "created /etc/apt/apt.conf.d/20auto-upgrades"
fi

# ---- Write explicit policy drop-in ----------------------------------------
MAIL_VAL="\"root\""; [[ -n "$EMAIL" ]] && MAIL_VAL="\"$EMAIL\""
reboot_val() { [[ "$REBOOT" =~ ^(true|1|yes)$ ]] && echo "true" || echo "false"; }
ini_reboot=$(reboot_val)

# Idempotent ini-style overrides via an apt.conf fragment.
CFG="/etc/apt/apt.conf.d/52-tavro-upgrades"

cat > "$CFG" <<EOF
// Managed by ops/security/03-auto-security-updates.sh
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::RandomSleep "300";
// Default origins: install security + the proposed "updates" for origin Ubuntu/Debian.
Unattended-Upgrade::Origins-Pattern {
        "origin=Ubuntu,archive=${UBUNTU_CODENAME:-jammy}-security";
        "origin=Debian,codename=${DEBIAN_CODENAME:-bookworm}-security,label=Debian-Security";
};
Unattended-Upgrade::Automatic-Reboot "${ini_reboot}";
Unattended-Upgrade::Automatic-Reboot-Time "04:00";
Unattended-Upgrade::Automatic-Reboot-WithUsers "false";
Unattended-Upgrade::Mail "${MAIL_VAL}";
Unattended-Upgrade::MailOnlyOnError "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Remove-New-Unused-Dependencies "true";
EOF
chmod 600 "$CFG"
ok "wrote policy -> $CFG"

# ---- Validate config ------------------------------------------------------
say "Validating effective apt auto-upgrade settings..."
auto_up=$(apt-config dump APT::Periodic::Unattended-Upgrade | grep -oP '(?<=")[01]' || true)
if [[ "$auto_up" == "1" ]]; then
  ok "APT::Periodic::Unattended-Upgrade is enabled"
else
  warn "apt does not report Unattended-Upgrade = 1; check /etc/apt/apt.conf.d/*.conf"
fi

printf "\n"
printf "${GRN}Automatic security updates configured.${NC}\n"
if [[ -n "$EMAIL" ]]; then
  ok "email notifications -> $EMAIL (errors only)"
else
  warn "no --email given; notifications off. Pass --email admin@example.com to enable."
fi
printf "Test with:  sudo unattended-upgrade --dry-run --debug\n"
