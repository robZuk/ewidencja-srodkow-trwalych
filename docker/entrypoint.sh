#!/usr/bin/env bash
# One-command dev bootstrap: prepares .env, installs deps, waits for the DB,
# runs migrations + seeders, then starts the app. Everything here is idempotent
# so `docker compose up` works from a fresh clone and on every restart.
set -euo pipefail

cd /var/www/html

log() { echo "[entrypoint] $*"; }

# 1. Environment file (never overwrite an existing one).
if [ ! -f .env ]; then
    log "creating .env from .env.docker"
    cp .env.docker .env
fi

# 2. Dependencies (only when missing, to keep restarts fast).
if [ ! -f vendor/autoload.php ]; then
    log "installing composer dependencies"
    composer install --no-interaction --no-progress
fi

if [ ! -d node_modules ]; then
    log "installing npm dependencies"
    npm install --no-audit --no-fund
fi

# 3. App key.
if ! grep -q '^APP_KEY=base64:' .env; then
    log "generating APP_KEY"
    php artisan key:generate --force
fi

# 4. Wait for the database to accept a real PDO connection.
log "waiting for database..."
until php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; do
    sleep 2
done
log "database is up"

# 5. Schema + seed data (local/dev convenience).
php artisan migrate --force
if [ "${APP_ENV:-local}" != "production" ]; then
    php artisan db:seed --force || log "seeding skipped/failed (non-fatal)"
fi

log "ready → http://localhost:8000"

# 6. Dispatch. `serve` runs Vite + the PHP dev server together; anything else
#    is executed verbatim (e.g. `docker compose run app php artisan ...`).
if [ "${1:-serve}" = "serve" ]; then
    npm run dev -- --host 0.0.0.0 &
    exec php artisan serve --host=0.0.0.0 --port=8000
fi

exec "$@"
