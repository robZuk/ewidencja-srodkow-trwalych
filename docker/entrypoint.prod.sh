#!/bin/sh
# Production start: wait for the database, apply schema + roles, warm the caches,
# then hand off to FrankenPHP as PID 1. Config comes from real env vars injected
# by docker-compose (env_file: .env.prod) — there is no .env file in the image.
set -e

cd /app

log() { echo "[entrypoint] $*"; }

# 1. Wait until PostgreSQL accepts connections (safety net; compose already gates
#    on the db healthcheck, but this also covers an external shared DB later).
log "waiting for database at ${DB_HOST}:${DB_PORT}..."
until php -r '
    try {
        new PDO(
            "pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD")
        );
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    sleep 2
done
log "database is up"

# 2. Schema + roles/permissions (RolePermissionSeeder is idempotent).
log "running migrations"
php artisan migrate --force
log "seeding roles & permissions"
php artisan db:seed --class='Database\Seeders\RolePermissionSeeder' --force

# 3. Warm caches. NOTE: no route:cache — routes/web.php uses a closure (logout),
#    which route caching cannot serialize.
log "caching config & views"
php artisan config:cache
php artisan view:cache

# 4. Serve.
log "starting FrankenPHP on :8080"
exec frankenphp run --config /etc/frankenphp/Caddyfile
