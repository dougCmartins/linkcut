#!/bin/sh
# Full backup of production, encrypted: database + uploaded images and login keys + .env.production.
# Usage: make prod-backup (runs this with PROD="docker compose -f docker-compose.prod.yml --env-file .env.production").
# Needs BACKUP_PASSPHRASE in .env.production (keep a copy in your password manager: without it nothing can be restored).
# Optional off-site copy: BACKUP_RCLONE_REMOTE (e.g. "r2:linkcut-backups") with rclone installed and configured.
set -eu

: "${PROD:?run through make prod-backup}"
ENV_FILE="${ENV_FILE:-.env.production}"
BACKUP_DIR="${BACKUP_DIR:-backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"

env_value() { grep -E "^$1=" "$ENV_FILE" | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
BACKUP_PASSPHRASE="$(env_value BACKUP_PASSPHRASE)"
RCLONE_REMOTE="$(env_value BACKUP_RCLONE_REMOTE || true)"
[ -n "$BACKUP_PASSPHRASE" ] || { echo "Falta BACKUP_PASSPHRASE no $ENV_FILE (docs/deploy-vps.md, Backup)."; exit 1; }
export BACKUP_PASSPHRASE

umask 077
mkdir -p "$BACKUP_DIR"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
stamp="$(date +%Y-%m-%d-%H%M)"

# 1. Database (consistent snapshot, no lock on the tables in use).
$PROD exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --no-tablespaces "$MYSQL_DATABASE"' \
  | gzip > "$work/database.sql.gz"

# 2. Uploaded images and the login keys (without them every session is lost).
$PROD exec -T app sh -c 'cd /var/www/html/storage && tar czf - app/public oauth-private.key oauth-public.key' > "$work/storage.tar.gz"

# 3. Settings: APP_KEY, VAPID keys, passwords.
cp "$ENV_FILE" "$work/env.production"

# One encrypted file (AES-256, key derived from the passphrase).
file="$BACKUP_DIR/linkcut-$stamp.tar.gz.enc"
tar czf - -C "$work" database.sql.gz storage.tar.gz env.production \
  | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$file"
chmod 600 "$file"
echo "Backup: $file ($(du -h "$file" | cut -f1))"

if [ -n "$RCLONE_REMOTE" ]; then
  if command -v rclone >/dev/null 2>&1; then
    rclone copy "$file" "$RCLONE_REMOTE" && echo "Cópia fora da VPS: $RCLONE_REMOTE"
  else
    echo "Aviso: BACKUP_RCLONE_REMOTE definido, mas o rclone não está instalado (docs/deploy-vps.md, Backup)."
  fi
fi

find "$BACKUP_DIR" -name 'linkcut-*.tar.gz.enc' -mtime +"$KEEP_DAYS" -delete
