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
    /** @var list<string> real-world Polish fixed-asset names */
    public const EQUIPMENT = [
        'Laptop', 'Komputer stacjonarny', 'Monitor', 'Drukarka laserowa', 'Skaner',
        'Projektor multimedialny', 'Biurko', 'Krzesło biurowe', 'Szafa aktowa',
        'Regał magazynowy', 'Telefon systemowy', 'Router', 'Przełącznik sieciowy',
        'Serwer', 'Zasilacz UPS', 'Niszczarka dokumentów', 'Kserokopiarka',
        'Tablica interaktywna', 'Ekran projekcyjny', 'Klimatyzator', 'Aparat fotograficzny',
        'Kamera cyfrowa', 'Mikroskop', 'Waga laboratoryjna', 'Wirówka laboratoryjna',
        'Oscyloskop', 'Drukarka 3D', 'Zestaw komputerowy', 'Dysk sieciowy NAS', 'Tablet',
    ];

    /** @var list<string> */
    public const BRANDS = [
        'Dell', 'HP', 'Lenovo', 'Samsung', 'LG', 'Epson', 'Canon', 'Brother',
        'Asus', 'Acer', 'BenQ', 'Fujitsu', '',
    ];

    /** Value threshold (PLN) separating low-value (ST_NIS) from high-value (ST_WYS). */
    public const HIGH_VALUE_THRESHOLD = 10000;

    /** Classify an asset by its unit value into the legacy type codes. */
    public static function typeForValue(float $value): string
    {
        return $value >= self::HIGH_VALUE_THRESHOLD ? 'ST_WYS' : 'ST_NIS';
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $purchase = fake()->dateTimeBetween('-15 years', '-1 month');
        $value = fake()->randomFloat(2, 10, 25000);

        return [
            'inventory_number' => sprintf(
                '%03d-%03d-%02d',
                fake()->numberBetween(1, 999),
                fake()->numberBetween(1, 999),
                fake()->numberBetween(1, 99),
            ),
            'name' => trim(fake()->randomElement(self::EQUIPMENT).' '.fake()->randomElement(self::BRANDS)),
            'description' => fake()->optional()->sentence(),
            'purchase_doc_number' => 'DOK/'.fake()->numberBetween(2000, 2025).'/'.fake()->numberBetween(1000, 9999),
            'value' => $value,
            'purchase_date' => $purchase,
            'liquidation_date' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'asset_type' => self::typeForValue($value),
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
