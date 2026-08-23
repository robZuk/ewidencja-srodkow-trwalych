# Wdrożenie na Mikr.us (Docker + PostgreSQL + FrankenPHP)

Aplikacja jest wdrażana jako samowystarczalny stack Docker Compose: kontener
aplikacji (**FrankenPHP** serwujący `public/`) + **PostgreSQL 16** na sieci
wewnętrznej. HTTPS zapewnia brzeg Mikr.us (subdomena) — w aplikacji nie ma
certyfikatów. Wzorzec jest taki jak w `planista7`.

Pliki: `docker/Dockerfile.prod`, `docker/entrypoint.prod.sh`, `docker/Caddyfile`,
`docker-compose.prod.yml`, `.env.prod.example`.

---

## 1. SSH na serwer

```bash
ssh root@eve250.mikrus.xyz -p10250      # host/port z panelu Mikr.us → Pulpit
```

## 2. Docker (Mikr.us to LXC)

```bash
curl -fsSL https://get.docker.com | sh
```

Jeśli Docker nie startuje (typowe w LXC):

```bash
apt-get install -y fuse-overlayfs
printf '{\n  "storage-driver": "fuse-overlayfs"\n}\n' > /etc/docker/daemon.json
systemctl restart docker
```

## 3. Kod na serwer

Docelowa ścieżka: `/srv/asset-inventory`.

```bash
mkdir -p /srv && cd /srv
git clone <repo-url> asset-inventory      # gdy repo trafi na GitHub
cd asset-inventory
```

Bez zdalnego repozytorium można wgrać kod przez `rsync`/`scp` do
`/srv/asset-inventory`.

## 4. Port TCP

Panel: **Sieć i domeny → Porty TCP**. Są 2 porty domyślne (np. `20250`, `30250`)
i limit 10 dodatkowych („Poproszę port TCP"). Wybrany port ustawiasz jako
`WEB_PORT` w `.env.prod` (aplikacja nasłuchuje wewnętrznie na `:8080`, a compose
mapuje `WEB_PORT:8080`).

## 5. Sekrety — `.env.prod`

```bash
cp .env.prod.example .env.prod
```

Uzupełnij w `.env.prod`:

- **`APP_KEY`** — wygeneruj:
  ```bash
  # bez kontenera:
  echo "base64:$(openssl rand -base64 32)"
  # lub przez obraz:
  docker compose --env-file .env.prod -f docker-compose.prod.yml \
    run --rm --no-deps --entrypoint php app artisan key:generate --show
  ```
- **`DB_PASSWORD`** — `openssl rand -base64 32`
- **`APP_URL`** — docelowa subdomena (np. `https://asset-xxxxx.wykr.es`)
- **`WEB_PORT`** — port z kroku 4

> `.env.prod` jest w `.gitignore` — nie commituj go. Ten sam plik służy do
> interpolacji compose (`--env-file`) i jako `env_file` kontenerów.

## 6. Start

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

Entrypoint przy starcie: czeka na bazę → `migrate --force` →
`RolePermissionSeeder` (idempotentny) → `config:cache` + `view:cache` → FrankenPHP.
(Świadomie **bez** `route:cache` — trasa `logout` używa closure, której nie da
się serializować.)

## 7. Konto administratora (jednorazowo)

`DemoSeeder` nie jest uruchamiany na produkcji — konto tworzysz ręcznie:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan tinker
```
```php
$u = App\Models\User::create([
    'name' => 'Imię Nazwisko',
    'email' => 'admin@twoja-domena',
    'password' => bcrypt('SILNE-HASLO'),
]);
$u->syncRoles('admin');
```

Role dostępne po seederze: `admin`, `editor`, `inventory_section`, `viewer`.

## 8. Test lokalny na serwerze

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:${WEB_PORT}/up   # → 200
```

## 9. Wystawienie na świat

Panel: **Sieć i domeny → Subdomeny** → przypnij subdomenę (`*.wykr.es`) do
`WEB_PORT`. HTTPS obsługuje brzeg Mikr.us. Ustaw `APP_URL` na tę subdomenę i
odśwież config cache:

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d
```

## 10. Redeploy

```bash
cd /srv/asset-inventory
git pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
```

Dane bazy są trwałe w wolumenie `pgdata` (przetrwają redeploy i reboot serwera;
`restart: unless-stopped` autostartuje kontenery po reboocie).

---

## Automatyczny deploy (CI/CD) — opcjonalnie, później

`.github/workflows/deploy.yml` (wzór z `planista7`) buduje obraz, wypycha do
**GHCR** i wdraża przez SSH. Aktywuje się po:

1. wypchnięciu repo na GitHub,
2. ustawieniu sekretów `MIKRUS_HOST`, `MIKRUS_PORT`, `MIKRUS_USER`, `MIKRUS_SSH_KEY`
   (Settings → Secrets and variables → Actions),
3. jednorazowym zalogowaniu serwera do GHCR (obrazy są prywatne):
   ```bash
   echo <GHCR_PAT> | docker login ghcr.io -u <github-user> --password-stdin
   ```

Wtedy każdy push do `main` buduje obraz i uruchamia na serwerze
`git pull` + `docker compose ... pull` + `up -d` (z `APP_IMAGE` wskazującym obraz z GHCR).

---

## Wiele aplikacji na jednym serwerze (docelowo)

Obecny stack jest samowystarczalny (własny Postgres, własny port). Gdy dojdą
kolejne apki Laravel, zalecana ścieżka:

- **wspólny reverse proxy** (Caddy) na jednym porcie, routing po subdomenach,
- **wspólna instancja PostgreSQL** (osobna baza per aplikacja) zamiast osobnego
  kontenera bazy na apkę (oszczędność RAM na 4 GB VPS),
- każda apka we własnym kontenerze aplikacji.

Przejście tej apki na wspólną bazę to zmiana `DB_HOST`/`DB_DATABASE` w `.env.prod`
(bez przebudowy obrazu).
