<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AssetForm extends Form
{
    public ?Asset $asset = null;

    public string $inventory_number = '';

    public string $name = '';

    public ?string $description = null;

    public ?string $purchase_doc_number = null;

    public string $value = '0';

    public ?string $purchase_date = null;

    public ?string $liquidation_date = null;

    public int $quantity = 1;

    public ?string $asset_type = null;

    public ?int $location_id = null;

    public ?int $inventory_field_id = null;

    public string $status = AssetStatus::Available->value;

    public ?string $comment = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'inventory_number' => [
                'required', 'string', 'max:255',
                Rule::unique('assets', 'inventory_number')
                    ->where(fn ($query) => $query->where('inventory_field_id', $this->inventory_field_id))
                    ->ignore($this->asset?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'purchase_doc_number' => ['nullable', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'liquidation_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'asset_type' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')],
            'inventory_field_id' => ['required', Rule::exists('inventory_fields', 'id')],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'comment' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'inventory_number' => 'numer inwentarzowy',
            'name' => 'nazwa',
            'value' => 'wartość',
            'quantity' => 'ilość',
            'inventory_field_id' => 'pole spisowe',
            'purchase_date' => 'data zakupu',
            'liquidation_date' => 'data likwidacji',
        ];
    }

    public function setAsset(Asset $asset): void
    {
        $this->asset = $asset;
        $this->inventory_number = $asset->inventory_number;
        $this->name = $asset->name;
        $this->description = $asset->description;
        $this->purchase_doc_number = $asset->purchase_doc_number;
        $this->value = (string) $asset->value;
        $this->purchase_date = $asset->purchase_date?->format('Y-m-d');
        $this->liquidation_date = $asset->liquidation_date?->format('Y-m-d');
        $this->quantity = $asset->quantity;
        $this->asset_type = $asset->asset_type;
        $this->location_id = $asset->location_id;
        $this->inventory_field_id = $asset->inventory_field_id;
        $this->status = $asset->status->value;
        $this->comment = $asset->comment;
    }

    public function store(): Asset
    {
        $this->validate();

        return Asset::create($this->except('asset'));
    }

    public function update(): void
    {
        $this->validate();

        $this->asset?->update($this->except('asset'));
    }
}
