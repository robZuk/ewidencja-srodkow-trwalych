<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Generates a realistic, self-contained demo dataset (no real data required).
 * Idempotent: safe to run repeatedly thanks to updateOrCreate on the accounts.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUsers();

        // Skip regenerating the (large) demo dataset if it already exists.
        if (Asset::query()->exists()) {
            return;
        }

        $fields = $this->createInventoryFields();
        $locations = Location::factory(15)->create();

        // ~60 assets spread across fields and locations, a few already liquidated.
        Asset::factory(54)
            ->recycle($fields)
            ->recycle($locations)
            ->create();

        Asset::factory(6)
            ->liquidated()
            ->recycle($fields)
            ->recycle($locations)
            ->create();

        // Touch a handful of assets so the audit trail has "updated" entries too.
        Asset::query()->inRandomOrder()->limit(8)->get()
            ->each(fn (Asset $asset) => $asset->update(['comment' => 'Zaktualizowano podczas przeglądu.']));

        $this->createTransferRequests($fields);
    }

    private function createUsers(): void
    {
        $accounts = [
            ['Robert (Admin)', 'admin@example.com', 'admin'],
            ['Edytor Środków', 'editor@example.com', 'editor'],
            ['Sekcja Inwentaryzacji', 'inwentaryzacja@example.com', 'inventory_section'],
            ['Konto Demo', 'demo@example.com', 'viewer'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role]);
        }
    }

    /** @return Collection<int, InventoryField> */
    private function createInventoryFields(): Collection
    {
        $names = [
            'Dziekanat', 'Biblioteka', 'Katedra Informatyki', 'Katedra Automatyki',
            'Rektorat', 'Dział IT', 'Laboratorium Fizyki', 'Magazyn Centralny',
        ];

        return collect($names)->map(fn (string $name, int $i) => InventoryField::create([
            'code' => str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
            'name' => $name,
        ]));
    }

    /** @param Collection<int, InventoryField> $fields */
    private function createTransferRequests(Collection $fields): void
    {
        $requester = User::where('email', 'editor@example.com')->first();

        // A pending transfer between two fields.
        $asset = Asset::query()->where('status', 'available')->inRandomOrder()->first();
        if ($asset !== null) {
            TransferRequest::create([
                'type' => TransferType::Transfer,
                'status' => TransferStatus::Pending,
                'asset_id' => $asset->id,
                'source_field_id' => $asset->inventory_field_id,
                'target_field_id' => $fields->where('id', '!=', $asset->inventory_field_id)->random()->id,
                'requested_by' => $requester?->id,
                'note' => 'Przeniesienie do nowej jednostki organizacyjnej.',
            ]);
        }

        // A pending liquidation request.
        $asset2 = Asset::query()->where('status', 'available')->inRandomOrder()->first();
        if ($asset2 !== null) {
            TransferRequest::create([
                'type' => TransferType::Liquidation,
                'status' => TransferStatus::PendingInventory,
                'asset_id' => $asset2->id,
                'source_field_id' => $asset2->inventory_field_id,
                'requested_by' => $requester?->id,
                'note' => 'Sprzęt uszkodzony, nie nadaje się do naprawy.',
            ]);
        }
    }
}
