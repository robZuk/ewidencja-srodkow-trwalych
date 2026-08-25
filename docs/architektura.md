# Architektura, Docker i CI/CD

Przewodnik po tym, jak aplikacja jest zbudowana, uruchamiana lokalnie i wdrażana
na produkcję. Uzupełnia [deploy-mikrus.md](deploy-mikrus.md) (runbook wdrożenia)
o warstwę „jak i dlaczego".

## Stack

- **Backend:** Laravel 13 (PHP 8.4), Livewire 4 + Flux, Spatie Permission.
- **Baza:** PostgreSQL 16 (dev, CI i produkcja — jeden silnik wszędzie).
- **Frontend:** Blade + Livewire, assety budowane przez Vite (Flux dostarcza CSS
  przez Composer).
- **Serwer WWW (prod):** FrankenPHP (serwer HTTP + PHP w jednym; zastępuje
  nginx + php-fpm).
- **Hosting:** VPS Mikr.us (eve250), Docker Compose, HTTPS terminowany na brzegu.

---

## Dwa środowiska

Ta sama aplikacja żyje w dwóch bardzo różnych „opakowaniach":

| | dev | produkcja |
|---|---|---|
| Dockerfile | `docker/Dockerfile` (1 etap) | `docker/Dockerfile.prod` (3 etapy) |
| Entrypoint | `docker/entrypoint.sh` | `docker/entrypoint.prod.sh` |
| Compose | `docker-compose.yml` | `docker-compose.prod.yml` |
| Kod aplikacji | bind-mount z dysku (żywy) | wpieczony w obraz (niezmienny) |
| Serwer | `artisan serve` + Vite (HMR) | FrankenPHP |
| Użytkownik procesu | użytkownik hosta (UID/GID) | `www-data` (non-root) |
| Zależności | pełne (z dev) | `--no-dev`, autoloader authoritative |
| Port bazy | 5432 wystawiony na host | brak — baza odcięta |
| Sekrety | jawne w compose (`secret`) | `.env.prod` (poza repo) |
| Seed | `DatabaseSeeder` (role + demo) | tylko `RolePermissionSeeder` |

Myśl przewodnia: **dev optymalizuje pod wygodę**, **prod pod bezpieczeństwo i
powtarzalność**.

---

## Obraz produkcyjny — `docker/Dockerfile.prod`

Build wieloetapowy; finalnym obrazem zostaje tylko ostatni etap, z pozostałych
kopiujemy jedynie gotowe artefakty (narzędzia zostają wyrzucone).

1. **`vendor`** (`composer:2`) — `composer install --no-dev` → produkuje `vendor/`.
   Kopiuje tylko `composer.json`/`composer.lock`, więc warstwa jest z cache dopóki
   zależności się nie zmienią.
2. **`frontend`** (`node:22`) — `npm ci` + `npm run build` → produkuje
   `public/build`. Kopiuje `vendor/` z etapu 1, bo `resources/css/app.css`
   importuje `vendor/livewire/flux/dist/flux.css`.
3. **`runtime`** (`dunglas/frankenphp`) — finalny obraz. Instaluje rozszerzenia
   PHP (tylko `pdo_pgsql`), wpieka kod (`COPY . .`, filtrowany przez
   `.dockerignore`), nakłada `vendor/` i `public/build` z poprzednich etapów,
   generuje zoptymalizowany autoloader (`--classmap-authoritative`), ustawia
   `APP_ENV=production`, `HEALTHCHECK` na `/up` i uruchamia jako `www-data`.

## Start kontenera prod — `docker/entrypoint.prod.sh`

Przy każdym starcie (czyli przy każdym deployu):

1. czeka na bazę (pętla PDO),
2. `migrate --force`,
3. `db:seed --class=RolePermissionSeeder --force` (tylko role — **żadnych danych
   demo**),
4. `config:cache` + `view:cache` — **bez `route:cache`** (trasa `logout` to
   closure, której nie da się serializować),
5. `exec frankenphp run` — serwuje na `:8080`.

Konfiguracja wchodzi przez zmienne środowiskowe z `.env.prod` — w obrazie nie ma
pliku `.env`.

## Konfiguracja serwera i PHP

- **`docker/Caddyfile`** — FrankenPHP serwuje `/app/public` na `:8080` po HTTP
  (`auto_https off`, bo TLS jest na brzegu Mikr.us), z kompresją i fallbackiem do
  `index.php` (`php_server`). `admin off` + `persist_config off` = bezstanowo,
  bez wystawionego API zarządzania.
- **`docker/php-prod.ini`** — OPcache włączony z `validate_timestamps=0` (nie
  sprawdza zmian plików → maks. wydajność; bezpieczne, bo obraz jest niezmienny,
  a redeploy restartuje PHP). `expose_php=0` ukrywa wersję PHP.

## Stack na serwerze — `docker-compose.prod.yml`

```
świat ──WEB_PORT(30250)──►  [app]  ──internal──►  [db]
                            edge+internal          tylko internal, bez portu
```

- **`app`** — jedyna usługa z wystawionym portem (`WEB_PORT:8080`); obraz z GHCR
  (`APP_IMAGE`), sekrety z `env_file: .env.prod`, `restart: unless-stopped`.
- **`db`** — `postgres:16-alpine`, dane w wolumenie `pgdata` (przeżywają
  redeploy/reboot), **bez portu**, tylko w sieci `internal: true`.
- **Dwie sieci** = defense in depth: baza jest osiągalna wyłącznie od strony
  aplikacji, niewidoczna z internetu i z hosta.

`.env.prod` pełni dwie role: źródło interpolacji `${…}` (przez `--env-file`) oraz
zmienne wstrzykiwane do kontenerów (przez `env_file:`).

---

## CI/CD

Dwa niezależne workflowy GitHub Actions + jeden reusable.

### `tests.yml` (reusable, `workflow_call`)
Bramka jakości w jednym miejscu:
- **quality** — Pint (styl), Larastan (analiza statyczna), Pest na PostgreSQL,
- **assets** — `composer install --no-dev` (dla Flux CSS) + `npm run build`.

### `ci.yml`
Na push do `main`/`master` i na PR — woła `tests.yml`.

### `deploy.yml`
Na push do `main` (pomija zmiany tylko w `**.md`/`docs/**`):
1. **`test`** — woła `tests.yml`;
2. **`build-push-deploy`** (`needs: test` → tylko po zielonych testach):
   - build obrazu z `Dockerfile.prod`, push do GHCR (`:latest` + `:<sha>`),
   - deploy po SSH (tylko gdy ustawiony sekret `MIKRUS_HOST`): `git pull` →
     `docker compose pull` → `up -d` → `prune`.

### Pełny przepływ

```
git push origin main
   ├─ CI (ci.yml) ────────────► test (quality + assets)
   └─ Deploy (deploy.yml)
        test ──(zielone?)──► build → GHCR → SSH → up -d
              └─(czerwone)──► build-push-deploy POMINIĘTY (nic nie leci na prod)
                                   │
                                   ▼
                    kontener restart → entrypoint.prod.sh
                    (wait DB → migrate → seed roli → cache → FrankenPHP :8080)
                                   ▼
                    brzeg Mikr.us (HTTPS) → https://srodkitrwale.tojest.dev
```

### Sekrety
- Repo → Secrets → Actions: `MIKRUS_HOST`, `MIKRUS_PORT`, `MIKRUS_USER`,
  `MIKRUS_SSH_KEY` (deploy po SSH). `GITHUB_TOKEN` (push do GHCR) jest
  automatyczny.
- `/srv/ewidencja-srodkow-trwalych/.env.prod` na serwerze (gitignored):
  `APP_KEY`, `DB_PASSWORD`, `APP_URL`, `WEB_PORT`, `APP_IMAGE`.

---

## Kluczowe decyzje projektowe (i dlaczego)

- **PostgreSQL wszędzie** — dev, CI i prod na tym samym silniku, żeby testy łapały
  różnice specyficzne dla PG (np. `ilike` zamiast `like` w wyszukiwarce —
  `App\Models\Asset::scopeSearch`).
- **FrankenPHP zamiast nginx+php-fpm** — jeden proces zamiast dwóch (+ supervisor);
  prostszy, jednokontenerowy, nowoczesny obraz idealny dla małego VPS-a.
- **Build wieloetapowy** — narzędzia (Composer, Node) tylko w etapach pośrednich;
  finalny obraz jest „chudy" i niezmienny.
- **Deploy gated na CI** (`needs: test`) — zepsuty kod nie trafi na produkcję mimo
  padających testów.
- **`URL::forceScheme('https')` w produkcji** (`AppServiceProvider`) — apka biegnie
  po HTTP za brzegiem terminującym TLS; bez tego Livewire generował endpoint po
  `http://` → blokada mixed-content na stronie `https://`.
- **Ten sam serwer co „planista"** — Docker już zainstalowany, GHCR zalogowany;
  asset-inventory używa portu 30250 (planista 20250). Docelowo można współdzielić
  jeden Postgres, by oszczędzić RAM na maszynie 4 GB.

## Mapa plików

| Plik | Rola |
|---|---|
| `docker/Dockerfile` / `.prod` | obraz dev / produkcyjny |
| `docker/entrypoint.sh` / `.prod.sh` | start kontenera dev / prod |
| `docker/Caddyfile` | konfiguracja serwera FrankenPHP (prod) |
| `docker/php-prod.ini` | strojenie PHP (OPcache) w prod |
| `docker-compose.yml` / `.prod.yml` | stack dev / prod |
| `.env.prod.example` | wzorzec sekretów produkcyjnych |
| `.github/workflows/tests.yml` | reusable — jakość (lint/analiza/testy/assety) |
| `.github/workflows/ci.yml` | woła tests.yml na push/PR |
| `.github/workflows/deploy.yml` | test → build → GHCR → SSH deploy |
| `docs/deploy-mikrus.md` | runbook wdrożenia (pierwszy deploy, redeploy, rollback) |
