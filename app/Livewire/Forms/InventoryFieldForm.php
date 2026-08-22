<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\InventoryField;
use Illuminate\Validation\Rule;
use Livewire\Form;

class InventoryFieldForm extends Form
{
    public ?InventoryField $field = null;

    public string $code = '';

    public string $name = '';

    public ?string $description = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('inventory_fields', 'code')->ignore($this->field?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'code' => 'kod',
            'name' => 'nazwa',
            'description' => 'opis',
        ];
    }

    public function setField(InventoryField $field): void
    {
        $this->field = $field;
        $this->code = $field->code;
        $this->name = $field->name;
        $this->description = $field->description;
    }

    public function store(): InventoryField
    {
        $this->validate();

        return InventoryField::create($this->only('code', 'name', 'description'));
    }

    public function update(): void
    {
        $this->validate();

        $this->field?->update($this->only('code', 'name', 'description'));
    }
}
