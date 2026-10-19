#!/bin/sh
# Gives the host user (HOST_UID/HOST_GID from the Makefile) ownership of the folders Laravel writes to.
set -eu

uid="${HOST_UID:-0}"
gid="${HOST_GID:-0}"

if [ "$uid" = "0" ]; then
  exit 0
fi

cd /var/www/html

targets="storage bootstrap/cache"
if [ -d vendor ]; then
  targets="$targets vendor"
fi

chown -R "$uid:$gid" $targets
find $targets -type d -exec chmod 775 {} +
find $targets -type f ! -name 'oauth-*.key' -exec chmod 664 {} +

for key in storage/oauth-private.key storage/oauth-public.key; do
  if [ -f "$key" ]; then
    chmod 660 "$key"
  fi
done
