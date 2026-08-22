<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $purchase = fake()->dateTimeBetween('-15 years', '-1 month');

        return [
            'inventory_number' => sprintf(
                '%03d-%03d-%02d',
                fake()->numberBetween(1, 999),
                fake()->numberBetween(1, 999),
                fake()->numberBetween(1, 99),
            ),
            'name' => ucfirst(fake()->words(fake()->numberBetween(1, 3), true)),
            'description' => fake()->optional()->sentence(),
            'purchase_doc_number' => 'DOK/'.fake()->numberBetween(2000, 2025).'/'.fake()->numberBetween(1000, 9999),
            'value' => fake()->randomFloat(2, 10, 25000),
            'purchase_date' => $purchase,
            'liquidation_date' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'asset_type' => fake()->randomElement(['ST_NIS', 'WNiP', 'POZ', ' MŚT']),
            'location_id' => Location::factory(),
            'inventory_field_id' => InventoryField::factory(),
            'status' => AssetStatus::Available,
            'comment' => null,
        ];
    }

    public function liquidated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Liquidated,
            'liquidation_date' => fake()->dateTimeBetween('-2 years', 'now'),
        ]);
    }
}
