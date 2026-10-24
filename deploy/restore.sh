#!/bin/sh
# Restores a backup made by deploy/backup.sh: database, uploaded images and login keys.
# Usage: make prod-restore FILE=backups/linkcut-AAAA-MM-DD-HHMM.tar.gz.enc
# The services must be up (make prod-up). The .env.production of the backup is saved next to it as
# .env.production.restored: compare it with the current one (the APP_KEY and VAPID keys must be the same).
set -eu

: "${PROD:?run through make prod-restore}"
: "${FILE:?informe o arquivo: make prod-restore FILE=backups/linkcut-....tar.gz.enc}"
ENV_FILE="${ENV_FILE:-.env.production}"

if [ -z "${BACKUP_PASSPHRASE:-}" ]; then
  BACKUP_PASSPHRASE="$(grep -E '^BACKUP_PASSPHRASE=' "$ENV_FILE" | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//')"
fi
[ -n "$BACKUP_PASSPHRASE" ] || { echo "Informe a senha do backup: BACKUP_PASSPHRASE=... make prod-restore FILE=..."; exit 1; }
export BACKUP_PASSPHRASE

umask 077
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
if ! openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$FILE" -out "$work/backup.tar.gz" 2>/dev/null \
  || ! tar xzf "$work/backup.tar.gz" -C "$work" 2>/dev/null; then
  echo "Não foi possível abrir o backup: senha errada ou arquivo corrompido."
  exit 1
fi

printf 'Isto SUBSTITUI o banco e as imagens atuais pelos do backup %s. Digite "restaurar" para continuar: ' "$FILE"
read -r answer
[ "$answer" = "restaurar" ] || { echo "Cancelado."; exit 1; }

gunzip -c "$work/database.sql.gz" | $PROD exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
$PROD exec -T app sh -c 'cd /var/www/html/storage && tar xzf - && chmod 600 oauth-private.key oauth-public.key' < "$work/storage.tar.gz"
cp "$work/env.production" .env.production.restored
chmod 600 .env.production.restored

echo "Restaurado. Confira o .env.production.restored (APP_KEY, VAPID_*) e rode make prod-up."
