#!/bin/sh
# node_modules lives in a Docker volume (fast on every OS), so it does not follow "git pull": when
# package-lock.json changes (a new dependency, like @sentry/vue), install again before starting Vite.
set -eu

cd /app

lock_hash="$(sha256sum package-lock.json | cut -d ' ' -f 1)"
stamp="node_modules/.package-lock.sha256"

if [ ! -f "$stamp" ] || [ "$(cat "$stamp")" != "$lock_hash" ]; then
  echo "package-lock.json changed: installing the dependencies (npm install)..."
  npm install --no-audit --no-fund
  echo "$lock_hash" > "$stamp"
fi

exec "$@"
