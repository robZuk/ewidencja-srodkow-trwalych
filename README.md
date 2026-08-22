# Asset Inventory

> A fixed-asset inventory and transfer-workflow system for an organisation — assets
> (*środki trwałe*), inventory fields (*pola spisowe*), a three-step transfer &
> liquidation approval flow, an audit trail, printable PDF forms and CSV/Excel export.

Built with **Laravel 13 · Livewire 4 · Volt · Flux · Tailwind v4**, tested with
**Pest**, statically analysed with **Larastan**, and runnable with a single
`docker compose up`.

> The domain is a rebuild of a real institutional asset-management application; this
> version is a clean, portfolio-focused reimplementation with a normalised schema,
> a service/action layer, policy-based authorization and full test coverage. The UI
> is in Polish (the domain language); the code, commits and docs are in English.

<!-- Replace <user> with your GitHub path once pushed. -->
![CI](https://github.com/<user>/asset-inventory/actions/workflows/ci.yml/badge.svg)

---

## Highlights

- **Assets CRUD** — searchable, filterable, sortable, paginated list; create/edit
  form with server-side validation; per-asset audit history.
- **Three-step transfer workflow** — an editor requests a transfer, the target field
  accepts, the inventory section confirms and the asset is moved — modelled as an
  explicit status state machine backed by dedicated Action classes.
- **Liquidation workflow** — request → inventory approval → asset marked liquidated.
- **Audit trail** — every asset change is recorded by an observer (append-only),
  replacing the legacy version's model-`boot()` side effects.
- **Role-based access** — `admin`, `editor`, `inventory_section`, `viewer`
  (spatie/laravel-permission) enforced through Policies and a read-only **demo** account.
- **Documents & export** — ZMU and liquidation **PDF** forms (dompdf), plus **CSV**
  and **Excel** export that honour the active list filters.
- **Legacy importer** — an idempotent, chunked artisan command to migrate real data
  from the old database schema into the new one.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| UI | Livewire 4 + Volt (SFCs) + Flux, Tailwind CSS v4 |
| Auth / RBAC | Laravel auth + spatie/laravel-permission |
| PDF / Spreadsheets | barryvdh/laravel-dompdf, phpoffice/phpspreadsheet |
| Testing / QA | Pest 4, Larastan (level 6), Laravel Pint |
| Tooling | Docker (PHP 8.4 + Node 22 + MySQL 8), GitHub Actions |

## Architecture

```
Livewire/Volt components   UI + interaction (thin — no business logic)
        │
        ├─ Livewire Form objects        validation + binding (AssetForm)
        ├─ Action classes               domain operations (App\Actions\Transfers\*)
        ▼
Eloquent models + query scopes         search()/forField()/withStatus(), enums
        │
        ├─ Policies + spatie/permission authorization (no magic role IDs)
        └─ AssetObserver                append-only audit trail
Thin controllers                       file endpoints only (PDF, CSV/XLSX)
```

Key decisions and how they differ from the legacy system are documented in
[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Domain model

- **Asset** (`assets`) — auto-increment PK, `unique(inventory_number, inventory_field_id)`,
  soft deletes, `status` enum (`available` / `transferred` / `liquidated`).
- **InventoryField** (`inventory_fields`) — the *pola spisowe* (a first-class table,
  not the misused `roles` table of the legacy schema).
- **Location** (`locations`), **TransferRequest** (`transfer_requests`, with `type`
  and `status` enums), **AssetActivity** (`asset_activities`, the audit log).

## Getting started

Requires Docker. From a fresh clone:

```bash
docker compose up
```

The entrypoint is idempotent: it creates `.env`, installs dependencies, waits for
MySQL, runs migrations and seeds demo data, then serves the app.

- App: <http://localhost:8000>
- Vite dev server: <http://localhost:5173>

> If your host user isn't UID 1000, start with
> `UID=$(id -u) GID=$(id -g) docker compose up`.

### Demo accounts

All passwords are `password`.

| Email | Role | Can |
|---|---|---|
| `admin@example.com` | admin | everything |
| `editor@example.com` | editor | manage assets, request transfers |
| `inwentaryzacja@example.com` | inventory_section | decide transfers, generate druki |
| `demo@example.com` | viewer | read-only (recruiter-friendly) |

## Testing & quality

```bash
docker compose exec app composer check   # Pint (style) + Larastan + Pest
# or individually:
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=1G
```

Tests run on an in-memory SQLite database; CI (GitHub Actions) runs the same gate
plus a production asset build on every push and pull request.

## Importing legacy data

The app ships with generated demo data. To import from the **old** database instead,
point the `legacy` connection at it and run the importer:

```dotenv
# .env
LEGACY_DB_HOST=...
LEGACY_DB_DATABASE=eki
LEGACY_DB_USERNAME=...
LEGACY_DB_PASSWORD=...
```

```bash
docker compose exec app php artisan app:import-legacy --dry-run   # preview counts
docker compose exec app php artisan app:import-legacy --fresh     # import (truncates first)
```

The command is chunked and idempotent, and maps the old schema onto the new one
(`roles`→`inventory_fields`, `zasoby`→`assets`, `powiadomienia`→`transfer_requests`,
etc.). It was verified end-to-end against a real ~20 000-asset dump.

## Screenshots

Screens: login (with one-click demo prefill), the assets list (search/filter/sort),
the asset form, the notifications/approval board, and the ZMU/liquidation *druki*.
Drop captures into `docs/screenshots/` to display them here.

## Deployment

The app is a standard Laravel + MySQL stack and deploys to any Docker host. See
[`docs/DEPLOY.md`](docs/DEPLOY.md) for a Railway/Fly.io walkthrough (a live demo URL
can be added here once deployed).

## Roadmap (v2)

- LDAP / SSO authentication
- User impersonation for support
- Full PL/EN interface localisation
- Bulk asset operations and saved filters

## Notes

Portfolio project. Demo data is generated with factories; any resemblance to real
records is coincidental. MIT-licensed.
