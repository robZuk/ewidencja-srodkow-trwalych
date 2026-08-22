<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use App\Models\TransferRequest;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Throwable;

/**
 * Imports data from the legacy EKI database (old Polish schema) into the new,
 * normalised schema. Idempotent and chunked; run with --dry-run to preview.
 *
 * Configure the source via LEGACY_DB_* env vars (see config/database.php).
 */
class ImportLegacy extends Command
{
    protected $signature = 'app:import-legacy
        {--dry-run : Report what would be imported without writing anything}
        {--fresh : Truncate the target tables before importing}';

    protected $description = 'Import assets, fields, locations and transfers from the legacy EKI database';

    /** Legacy role ids that were application roles, not inventory fields. */
    private const APP_ROLE_IDS = [999998, 999999];

    /** @var array<int, int> legacy roles.id => new inventory_fields.id */
    private array $fieldMap = [];

    /** @var array<string, int> location name => new locations.id */
    private array $locationMap = [];

    public function handle(): int
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot connect to the legacy database. Check LEGACY_DB_* in your .env.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be written.');
            $this->previewCounts();

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->truncateTargets();
        }

        // Skip the audit observer while bulk-importing.
        Model::withoutEvents(function (): void {
            $this->importInventoryFields();
            $this->importLocations();
            $this->importAssets();
            $this->importTransferRequests();
        });

        $this->newLine();
        $this->info('Import complete.');
        $this->summary();

        return self::SUCCESS;
    }

    private function previewCounts(): void
    {
        $rows = [];
        foreach (['roles', 'lokalizacje', 'zasoby', 'powiadomienia'] as $table) {
            $count = $this->legacyHas($table)
                ? DB::connection('legacy')->table($table)->count()
                : '(brak tabeli)';
            $rows[] = [$table, $count];
        }

        $this->table(['Tabela legacy', 'Wiersze'], $rows);
    }

    private function importInventoryFields(): void
    {
        if (! $this->legacyHas('roles')) {
            return;
        }

        DB::connection('legacy')->table('roles')
            ->whereNotIn('id', self::APP_ROLE_IDS)
            ->orderBy('id')
            ->each(function (stdClass $role): void {
                $field = InventoryField::updateOrCreate(
                    ['code' => (string) $role->id],
                    ['name' => $role->name ?? ('Pole '.$role->id)],
                );

                $this->fieldMap[(int) $role->id] = $field->id;
            });

        $this->line('Pola spisowe: '.count($this->fieldMap));
    }

    private function importLocations(): void
    {
        if (! $this->legacyHas('lokalizacje')) {
            return;
        }

        DB::connection('legacy')->table('lokalizacje')->orderBy('id')
            ->each(function (stdClass $row): void {
                $this->resolveLocation($row->Lokalizacja ?? null);
            });

        $this->line('Lokalizacje: '.count($this->locationMap));
    }

    private function importAssets(): void
    {
        if (! $this->legacyHas('zasoby')) {
            return;
        }

        $imported = 0;
        $bar = $this->output->createProgressBar((int) DB::connection('legacy')->table('zasoby')->count());

        DB::connection('legacy')->table('zasoby')->orderBy('Numer_Inwentarzowy')
            ->chunk(500, function ($rows) use (&$imported, $bar): void {
                foreach ($rows as $row) {
                    $fieldId = $this->fieldMap[(int) $row->Numer_Pola_Spisowego] ?? null;
                    if ($fieldId === null) {
                        $bar->advance();

                        continue; // orphaned asset — no matching inventory field
                    }

                    Asset::updateOrCreate(
                        [
                            'inventory_number' => $row->Numer_Inwentarzowy,
                            'inventory_field_id' => $fieldId,
                        ],
                        [
                            'name' => $row->Nazwa ?? '(bez nazwy)',
                            'description' => $row->Opis ?? null,
                            'purchase_doc_number' => $row->Numer_Dok_Zakupu ?? null,
                            'value' => $row->Wartosc ?? 0,
                            'purchase_date' => $this->date($row->Data_Zakupu ?? null),
                            'liquidation_date' => $this->date($row->Data_Likwidacji ?? null),
                            'quantity' => (int) ($row->Ilosc ?? 1),
                            'asset_type' => $row->Srodek ?? null,
                            'location_id' => $this->resolveLocation($row->Lokalizacja ?? null),
                            'status' => $this->mapAssetStatus($row->Status ?? null)->value,
                            'comment' => $row->Komentarz ?? null,
                        ],
                    );

                    $imported++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line("Środki: {$imported}");
    }

    private function importTransferRequests(): void
    {
        if (! $this->legacyHas('powiadomienia')) {
            return;
        }

        $imported = 0;

        DB::connection('legacy')->table('powiadomienia')->orderBy('id')
            ->each(function (stdClass $row) use (&$imported): void {
                $sourceId = $this->fieldMap[(int) ($row->pole_spisowe_zrodlowe ?? 0)] ?? null;
                if ($sourceId === null) {
                    return; // cannot satisfy the required source field FK
                }

                $isLiquidation = str_contains((string) ($row->typ ?? ''), 'likwidacj');

                TransferRequest::create([
                    'type' => $isLiquidation ? TransferType::Liquidation : TransferType::Transfer,
                    'status' => $this->mapTransferStatus($row->status ?? null, $isLiquidation)->value,
                    'asset_snapshot' => $this->decodeJson($row->dane_srodka ?? null),
                    'source_field_id' => $sourceId,
                    'target_field_id' => $this->fieldMap[(int) ($row->pole_spisowe_docelowe ?? 0)] ?? null,
                    'note' => $row->notatka ?? null,
                    'created_at' => $this->date($row->created_at ?? null),
                    'updated_at' => $this->date($row->updated_at ?? null),
                ]);

                $imported++;
            });

        $this->line("Zgłoszenia: {$imported}");
    }

    private function resolveLocation(?string $name): ?int
    {
        $name = $name !== null ? trim($name) : '';
        if ($name === '') {
            return null;
        }

        return $this->locationMap[$name] ??= Location::firstOrCreate(['name' => $name])->id;
    }

    private function mapAssetStatus(?string $status): AssetStatus
    {
        return str_contains(mb_strtolower((string) $status), 'likwid')
            ? AssetStatus::Liquidated
            : AssetStatus::Available;
    }

    private function mapTransferStatus(?string $status, bool $isLiquidation): TransferStatus
    {
        return match (mb_strtolower((string) $status)) {
            'zaakceptowane' => TransferStatus::Completed,
            'odrzucone' => TransferStatus::Rejected,
            default => $isLiquidation ? TransferStatus::PendingInventory : TransferStatus::Pending,
        };
    }

    private function date(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return (string) Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    private function decodeJson(?string $json): ?array
    {
        if (blank($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function legacyHas(string $table): bool
    {
        return Schema::connection('legacy')->hasTable($table);
    }

    private function truncateTargets(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['asset_activities', 'transfer_requests', 'assets', 'locations', 'inventory_fields'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        $this->warn('Target tables truncated (--fresh).');
    }

    private function summary(): void
    {
        $this->table(['Tabela', 'Wiersze'], [
            ['inventory_fields', InventoryField::count()],
            ['locations', Location::count()],
            ['assets', Asset::count()],
            ['transfer_requests', TransferRequest::count()],
        ]);
    }
}
