# Deployment

The app is a Laravel 13 + **PostgreSQL 16** application, served in production by
**FrankenPHP** in a Docker container. The supported target is a **Mikr.us VPS** —
see the step-by-step runbook:

➡️ **[docs/deploy-mikrus.md](deploy-mikrus.md)**

## In short

Production runs as a self-contained Docker Compose stack
(`docker-compose.prod.yml`):

- **app** — `docker/Dockerfile.prod` (FrankenPHP serving `public/` on `:8080`,
  HTTP; TLS is terminated at the Mikr.us edge).
- **db** — `postgres:16-alpine` on an internal-only network, data in the `pgdata`
  volume.

Config/secrets come from `.env.prod` (copy `.env.prod.example`), used both for
compose interpolation (`--env-file .env.prod`) and injected into the containers
(`env_file`). There is no `.env` file baked into the image.

On container start the entrypoint (`docker/entrypoint.prod.sh`) runs
`migrate --force`, seeds roles (`RolePermissionSeeder`, idempotent), and warms
`config:cache` + `view:cache` (no `route:cache` — the `logout` route is a
closure), then starts FrankenPHP.

```bash
# first deploy / redeploy on the server
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

## Other hosts

Any Docker-capable host works with the same `docker-compose.prod.yml`. Adjust
`WEB_PORT`, point `DB_HOST` at your database, and terminate TLS at your proxy /
load balancer. A pre-built image can be published to a registry via
`.github/workflows/deploy.yml` (GHCR) — inactive until the repo is on GitHub with
the `MIKRUS_*` secrets set.

## Notes

- Generate a fresh `APP_KEY` for production; never reuse the development key from
  `.env.docker`.
- No public demo account is seeded in production. Create an admin manually
  (see the runbook, step 7). `DemoSeeder` remains available if you want a demo.
