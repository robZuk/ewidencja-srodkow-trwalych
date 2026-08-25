# Wdrożenie

Aplikacja to Laravel 13 + **PostgreSQL 16**, serwowana na produkcji przez
**FrankenPHP** w kontenerze Docker. Wspieranym celem jest **VPS Mikr.us** — patrz
krok po kroku:

➡️ **[docs/deploy-mikrus.md](deploy-mikrus.md)**

## W skrócie

Produkcja działa jako samowystarczalny stack Docker Compose
(`docker-compose.prod.yml`):

- **app** — `docker/Dockerfile.prod` (FrankenPHP serwuje `public/` na `:8080`,
  HTTP; TLS terminowany na brzegu Mikr.us).
- **db** — `postgres:16-alpine` w sieci wyłącznie wewnętrznej, dane w wolumenie
  `pgdata`.

Konfiguracja/sekrety pochodzą z `.env.prod` (kopia `.env.prod.example`), używanego
zarówno do interpolacji compose (`--env-file .env.prod`), jak i wstrzykiwanego do
kontenerów (`env_file`). W obrazie nie ma pliku `.env`.

Przy starcie kontenera entrypoint (`docker/entrypoint.prod.sh`) wykonuje
`migrate --force`, seeduje role (`RolePermissionSeeder`, idempotentnie) i rozgrzewa
`config:cache` + `view:cache` (bez `route:cache` — trasa `logout` to closure), po
czym uruchamia FrankenPHP.

```bash
# pierwszy deploy / redeploy na serwerze
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

## CI/CD

Push do `main` uruchamia GitHub Actions (`.github/workflows/deploy.yml`): testy →
build obrazu → push do **GHCR** → deploy po SSH na serwer (`git pull` +
`docker compose pull` + `up -d`). Krok deploy wykonuje się tylko przy zielonym CI
i gdy ustawione są sekrety `MIKRUS_*`. Szczegóły w
[docs/deploy-mikrus.md](deploy-mikrus.md).

## Inne hosty

Każdy host z Dockerem zadziała z tym samym `docker-compose.prod.yml`. Dostosuj
`WEB_PORT`, wskaż `DB_HOST` na swoją bazę i terminuj TLS na swoim proxy / load
balancerze.

## Uwagi

- Wygeneruj świeży `APP_KEY` dla produkcji; nigdy nie używaj klucza deweloperskiego
  z `.env.docker`.
- Entrypoint **nie** seeduje danych demo — konto admina tworzysz ręcznie (patrz
  runbook, krok 7). `DemoSeeder` można odpalić ręcznie, jeśli chcesz dane demo (tak
  zrobiono na wersji demo pod publicznym adresem).
