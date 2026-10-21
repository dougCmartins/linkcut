#!/bin/sh
# Prepares the Laravel app and then runs the container command
# (artisan serve for "backend", queue:work for "queue", pulse:check for "pulse", schedule:work for "scheduler").
set -eu

cd /var/www/html

if [ "$(id -u)" = "0" ]; then
  if [ "${HOST_UID:-0}" != "0" ]; then
    sh docker/fix-permissions.sh
    exec gosu "${HOST_UID}:${HOST_GID:-$HOST_UID}" "$0" "$@"
  fi

  chown -R app:app storage bootstrap/cache
  # Production mounts .env.production as .env: the app writes APP_KEY and the VAPID keys there on the first start.
  if [ -f .env ]; then
    chown app:app .env
    chmod 600 .env
  fi
  if [ -d vendor ]; then
    chown -R app:app vendor
  fi
  find storage bootstrap/cache -type d -exec chmod 750 {} +
  find storage bootstrap/cache -type f ! -name 'oauth-*.key' -exec chmod 640 {} +
  exec gosu app:app "$0" "$@"
fi

export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-mysql}"
export DB_PORT="${DB_PORT:-3306}"
export DB_DATABASE="${DB_DATABASE:-linkcut}"
export DB_USERNAME="${DB_USERNAME:-linkcut}"
export DB_PASSWORD="${DB_PASSWORD:-secret}"

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Development installs new dependencies after a git pull; the production image already has them (no dev packages).
if [ ! -f vendor/autoload.php ] || { [ "${RUN_SETUP:-true}" = "true" ] && [ "${APP_ENV:-local}" != "production" ]; }; then
  composer install --no-interaction --prefer-dist
fi

echo "Waiting for MySQL..."
until php -r 'try { new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE")), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }'; do
  sleep 2
done

# Only the main backend container prepares keys and the database (avoids races with the workers).
if [ "${RUN_SETUP:-true}" = "true" ]; then
  if ! grep -Eq '^APP_KEY=.+' .env; then
    php artisan key:generate --ansi
  fi

  if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --ansi
  fi
  for key in storage/oauth-private.key storage/oauth-public.key; do
    chmod 600 "$key"
  done

  php artisan migrate --force

  # Push notification keys (VAPID), written to .env once.
  php artisan notifications:vapid-keys

  # Uploaded images are served from storage/app/public.
  if [ ! -e public/storage ]; then
    php artisan storage:link --ansi
  fi

  # Personal access client used to issue the access tokens after 2FA (idempotent).
  php artisan passport:ensure-personal-client
fi

exec "$@"
