# VPS Security Hardening

Current date: 2026-08-31 · Target: **Ubuntu 22.04 / 24.04** fresh VPS.

Three concerns covered:

1. **SSH hardening** — non-root sudo user, key-only auth, root login disabled,
   password auth disabled, optional custom port.
2. **Firewall** — UFW, deny everything inbound by default, allow only the
   explicit list.
3. **Automatic security updates** — `unattended-upgrades` installs security
   patches automatically, reboots only when a security update requires it.

## Files

| Script | Purpose |
|--------|---------|
| `01-ssh-hardening.sh` | Creates sudo user, installs pubkey, locks down `sshd_config`. |
| `02-firewall.sh` | UFW allowlist, deny-by-default. |
| `03-auto-security-updates.sh` | `unattended-upgrades` config. |
| `run-all.sh` | Runs all three in safe order. |

All scripts are **idempotent** (safe to re-run).

## Recommended run order (on the server)

Copy everything up first, from your laptop:

```bash
scp -r ops/security root@<server-ip>:/root/security-hardening
```

Then SSH in as root and run:

```bash
cd /root/security-hardening
sudo bash run-all.sh deploy "ssh-ed25519 AAAA..." --ssh-port 2222 --ports "80,443"
```

- Replace `deploy` with your chosen non-root username.
- Replace the `ssh-ed25519 AAAA...` key with **your** public key
  (your laptop's `~/.ssh/id_ed25519.pub`).
- `--ssh-port` and `--ports` are optional. Defaults: SSH port `22`, web `80,443`.

> ⚠️ **The single most important step — avoid locking yourself out.**
> SSH hardening **does not restart sshd automatically**. After `run-all.sh`
> finishes:
> 1. Open a **second terminal** and verify you can log in as the new user:
>    `ssh -p 2222 deploy@<server-ip>`
> 2. Only when that works, go back to the first terminal and run:
>    `sudo systemctl reload sshd`
> 3. If the new login fails, re-enable password/root login by restoring the
>    backup (`/etc/ssh/sshd_config.bak.*`) and `sudo systemctl reload sshd`.

## What each script changes

### SSH (`01-ssh-hardening.sh`)
- Creates the non-root user and grants passwordless sudo
  (`/etc/sudoers.d/90-<user>`). **This is your recommended daily account.**
- Installs your public key to
  `~<user>/.ssh/authorized_keys` (mode 600).
- Backs up `/etc/ssh/sshd_config` to `sshd_config.bak.<timestamp>`.
- Writes a drop-in `/etc/ssh/sshd_config.d/99-tavro-hardening.conf`:
  - `Port <chosen>` (default 22)
  - `PermitRootLogin no`
  - `PasswordAuthentication no`
  - `PubkeyAuthentication yes`
  - `KbdInteractiveAuthentication no`, `UsePAM no`
  - `PermitEmptyPasswords no`, `MaxAuthTries 4`, `LoginGraceTime 45`
  - `X11Forwarding no`, `AllowAgentForwarding no`, `AllowTcpForwarding no`
- Runs `sshd -t` to validate; rolls back to the backup if invalid.
- Leaves the reload to you (see the safety step above).

### Firewall (`02-firewall.sh`)
- `ufw default deny incoming` and `allow outgoing`.
- Always opens your SSH port **first** (never locks you out).
- Opens each port in the allowlist (default `80,443`).
- `ufw --force enable`, then prints the final rules for review.

Typical additions for Tavro once you add app ports:

```bash
sudo ufw allow 8080/tcp   # Reverb websocket
sudo ufw allow 3306/tcp   # MySQL (or keep DB bound to localhost only)
```

> Prefer binding services to `127.0.0.1` and not opening ports to the world
> unless the public needs them.

### Automatic security updates (`03-auto-security-updates.sh`)
- Installs `unattended-upgrades` if missing.
- Writes `/etc/apt/apt.conf.d/52-tavro-upgrades`:
  - Security updates: auto-install.
  - Other updates: downloaded only.
  - Reboot only when a security update requires it, at 04:00.
  - Randomized `RandomSleep 300`.
  - Remove unused dependencies automatically.
- Email notifications are **off by default**. Enable with
  `--email admin@example.com` (mail only on errors).

Verify afterwards:

```bash
sudo unattended-upgrade --dry-run --debug
sudo ufw status verbose
```

## Notes / caveats
- These scripts assume a **fresh** box, so they are deliberately strict.
- If you use a cloud firewall (AWS Security Group / cloud provider UI), keep it
  in sync: that firewall and UFW are additive — block in the tightest place.
- `passwordless sudo` is a convenience; if you prefer a password for `sudo`,
  remove `/etc/sudoers.d/90-<user>` and add the user to the `sudo` group
  (`usermod -aG sudo <user>`), then set a password.
- Always keep at least one console/out-of-band access path (your VPS console)
  in case SSH is ever misconfigured.
