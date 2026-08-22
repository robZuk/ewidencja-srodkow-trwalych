# Architecture & design decisions

This document explains how the application is structured and why — in particular how
it improves on the legacy system it was rebuilt from.

## Layers

```
Livewire/Volt component  →  Form object / Action  →  Eloquent model  →  DB
                         →  Policy (authorization)
                         →  Observer (audit)
```

- **Components stay thin.** Volt single-file components handle UI state and delegate
  domain work to Action classes or Form objects. No raw SQL, no business rules in the
  view layer.
- **Actions encapsulate operations.** Each workflow step is its own class in
  `App\Actions\Transfers` (`RequestTransfer`, `AcceptTransfer`, `AcceptLiquidation`,
  `RejectRequest`, …). They are unit-testable in isolation and injected into components.
- **Form objects** (`App\Livewire\Forms\AssetForm`) own validation rules and the
  create/update mapping, keeping the component small.
- **Query scopes** on `Asset` (`search`, `forField`, `withStatus`) replace ad-hoc
  `where()` chains and are reused by the list and the exporter.
- **Enums** (`AssetStatus`, `TransferType`, `TransferStatus`) replace magic strings and
  carry their own Polish labels and colours plus the workflow's `isOpen()` logic.
- **Authorization** is policy-based (`AssetPolicy`, `TransferRequestPolicy`) on top of
  spatie/laravel-permission roles and permissions.
- **Auditing** is handled by `AssetObserver`, writing an append-only `asset_activities`
  log.

## The transfer / liquidation state machine

`TransferStatus`:

```
Transfer:    Pending ──▶ PendingInventory ──▶ Completed
                    └─────────┬───────────────▶ Rejected
Liquidation: PendingInventory ──▶ Completed
                    └───────────▶ Rejected
```

Transitions live in the Action classes; asset side effects (moving an asset to the
target field, or marking it liquidated with a date) run inside a DB transaction.

## What changed versus the legacy system

| Legacy | This rebuild | Why |
|---|---|---|
| `roles` table reused as "pola spisowe"; magic role IDs `999998/999999` | dedicated `inventory_fields` table; named roles via spatie | clarity, referential integrity, real RBAC |
| `zasoby.id` = concatenated string PK | auto-increment PK + `unique(inventory_number, inventory_field_id)` | correctness, simpler relations |
| Fat controllers (800–1100 lines) with raw `DB::` queries | thin components + Actions + scopes | testability, separation of concerns |
| Audit logic inside model `boot()` | dedicated `AssetObserver` | single responsibility |
| Base migrations git-ignored; deploy by copying the DB | full migrations + factories + seeders | reproducible from a clean clone |
| Institution/LDAP specifics hard-coded | removed; generic auth + optional legacy import | portability |

## Testing strategy

- **Unit** — enums and Action logic.
- **Feature** — Volt component interactions (`Livewire::test`), HTTP endpoints,
  authorization matrices (data-driven), the importer (against a synthetic in-memory
  legacy DB), and PDF/export responses.
- Everything runs on SQLite in-memory for speed; the same gate runs in CI.
