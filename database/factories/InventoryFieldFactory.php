<?php

namespace Database\Factories;

use App\Models\InventoryField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryField>
 */
class InventoryFieldFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(1, 999),
            'name' => 'Pole '.fake()->unique()->company(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
