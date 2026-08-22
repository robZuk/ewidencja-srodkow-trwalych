<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferRequest>
 */
class TransferRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => TransferType::Transfer,
            'status' => TransferStatus::Pending,
            'asset_id' => Asset::factory(),
            'asset_snapshot' => null,
            'source_field_id' => InventoryField::factory(),
            'target_field_id' => InventoryField::factory(),
            'requested_by' => User::factory(),
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function liquidation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransferType::Liquidation,
            'status' => TransferStatus::PendingInventory,
            'target_field_id' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransferStatus::Completed,
            'resolved_at' => now(),
        ]);
    }
}
