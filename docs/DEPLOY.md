# Deployment

The app is a standard Laravel 13 + MySQL 8 application. Any Docker-capable host works;
below are notes for two common PaaS options. A live demo URL can be added to the README
once deployed.

## Prerequisites

- A MySQL 8 database (managed plugin or container).
- Environment variables (see `.env.docker` for the full list). At minimum:

```dotenv
APP_NAME="Asset Inventory"
APP_ENV=production
APP_KEY=            # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://your-domain

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Release steps

Run once per deploy, after the code is in place and the DB is reachable:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # roles/permissions
# optional demo data for a public demo:
php artisan db:seed --class=DemoSeeder --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Serve with `php artisan serve` behind a proxy, or php-fpm + nginx, or Octane.

## Railway

1. New project → **Deploy from repo**; add the **MySQL** plugin.
2. Set the env vars above (Railway injects the DB connection variables — map them to
   `DB_*`).
3. Add a release/start command that runs the release steps then serves the app.

## Fly.io

1. `fly launch` (detects the Dockerfile), `fly mysql create` (or an external DB).
2. `fly secrets set APP_KEY=... DB_...=...`
3. Add the release steps as a `[deploy] release_command` and start the server in the
   process group.

## Notes

- Generate a fresh `APP_KEY` for production; do not reuse the development key in
  `.env.docker`.
- For a public read-only demo, seed `DemoSeeder` and hand out the `demo@example.com`
  account (password `password`).
- Deploying is an account-specific action — it requires your own PaaS/cloud
  credentials and is intentionally left as a manual step.
