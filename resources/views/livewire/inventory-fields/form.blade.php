<?php

use App\Livewire\Forms\InventoryFieldForm;
use App\Models\InventoryField;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Pole spisowe'])] class extends Component
{
    public InventoryFieldForm $form;

    public bool $editing = false;

    public function mount(?InventoryField $inventoryField = null): void
    {
        if ($inventoryField !== null && $inventoryField->exists) {
            $this->authorize('update', $inventoryField);
            $this->editing = true;
            $this->form->setField($inventoryField);
        } else {
            $this->authorize('create', InventoryField::class);
        }
    }

    public function save(): void
    {
        if ($this->editing) {
            $this->form->update();
            session()->flash('status', 'Pole spisowe zostało zaktualizowane.');
        } else {
            $this->form->store();
            session()->flash('status', 'Pole spisowe zostało dodane.');
        }

        $this->redirectRoute('inventory-fields.index', navigate: true);
    }
}; ?>

<div class="mx-auto max-w-xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ $editing ? 'Edytuj pole spisowe' : 'Nowe pole spisowe' }}</flux:heading>
        <flux:subheading>Kod jednostki oraz jej nazwa.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
            <flux:input wire:model="form.code" label="Kod" placeholder="np. 001" required />
            <flux:input wire:model="form.name" label="Nazwa" placeholder="np. Dziekanat" required />
            <flux:textarea wire:model="form.description" label="Opis" rows="2" placeholder="opcjonalnie" />
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ $editing ? 'Zapisz zmiany' : 'Dodaj pole' }}</flux:button>
            <flux:button :href="route('inventory-fields.index')" variant="ghost" wire:navigate>Anuluj</flux:button>
        </div>
    </form>
</div>
