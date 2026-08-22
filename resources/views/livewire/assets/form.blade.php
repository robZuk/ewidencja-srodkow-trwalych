<?php

use App\Actions\Transfers\RequestLiquidation;
use App\Actions\Transfers\RequestTransfer;
use App\Enums\AssetStatus;
use App\Livewire\Forms\AssetForm;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use App\Models\TransferRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Środek'])] class extends Component
{
    public AssetForm $form;

    public bool $editing = false;

    // "Operacje na środku" panel (edit screen only).
    public ?int $targetFieldId = null;

    public string $transferNote = '';

    public string $liquidationNote = '';

    public function mount(?Asset $asset = null): void
    {
        if ($asset !== null && $asset->exists) {
            if ($asset->isLockedForEditing()) {
                session()->flash('status', 'Środek jest w trakcie akceptacji i nie może być teraz edytowany.');
                $this->redirectRoute('assets.index', navigate: true);

                return;
            }

            $this->authorize('update', $asset);
            $this->editing = true;
            $this->form->setAsset($asset);
        } else {
            $this->authorize('create', Asset::class);
        }
    }

    public function save(): void
    {
        if ($this->editing) {
            $this->form->update();
            session()->flash('status', 'Środek został zaktualizowany.');
        } else {
            $this->form->store();
            session()->flash('status', 'Środek został dodany.');
        }

        $this->redirectRoute('assets.index', navigate: true);
    }

    /** Start a transfer of this asset to another inventory field. */
    public function requestTransfer(RequestTransfer $action): void
    {
        $this->authorize('create', TransferRequest::class);

        $asset = $this->form->asset;
        abort_if($asset === null, 404);

        $this->validate([
            'targetFieldId' => ['required', 'exists:inventory_fields,id'],
        ], attributes: ['targetFieldId' => 'pole docelowe']);

        if ((int) $this->targetFieldId === (int) $asset->inventory_field_id) {
            $this->addError('targetFieldId', 'Pole docelowe musi być inne niż obecne pole środka.');

            return;
        }

        $action->handle(
            $asset,
            InventoryField::findOrFail($this->targetFieldId),
            auth()->user(),
            $this->transferNote !== '' ? $this->transferNote : null,
        );

        session()->flash('status', 'Utworzono przekazanie — środek oczekuje na akceptację.');
        $this->redirectRoute('transfers.index', navigate: true);
    }

    /** Start a liquidation request for this asset. */
    public function requestLiquidation(RequestLiquidation $action): void
    {
        $this->authorize('create', TransferRequest::class);

        $asset = $this->form->asset;
        abort_if($asset === null, 404);

        $action->handle(
            $asset,
            auth()->user(),
            $this->liquidationNote !== '' ? $this->liquidationNote : null,
        );

        session()->flash('status', 'Utworzono wniosek o likwidację — środek oczekuje na akceptację.');
        $this->redirectRoute('transfers.index', navigate: true);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'fields' => InventoryField::query()->orderBy('code')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'statuses' => AssetStatus::cases(),
        ];
    }
}; ?>

<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ $editing ? 'Edytuj środek' : 'Nowy środek' }}</flux:heading>
        <flux:subheading>Uzupełnij dane środka trwałego.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-950">
            <flux:input wire:model="form.inventory_number" label="Numer inwentarzowy" required />
            <flux:input wire:model="form.name" label="Nazwa" required />

            <flux:select wire:model="form.inventory_field_id" label="Pole spisowe" required>
                <flux:select.option value="">— wybierz —</flux:select.option>
                @foreach ($fields as $field)
                    <flux:select.option value="{{ $field->id }}">{{ $field->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.location_id" label="Lokalizacja">
                <flux:select.option value="">— brak —</flux:select.option>
                @foreach ($locations as $location)
                    <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="form.value" type="number" step="0.01" label="Wartość (zł)" required />
            <flux:input wire:model="form.quantity" type="number" min="1" label="Ilość" required />

            <flux:input wire:model="form.purchase_date" type="date" label="Data zakupu" />
            <flux:input wire:model="form.liquidation_date" type="date" label="Data likwidacji" />

            <flux:input wire:model="form.asset_type" label="Środek (typ)" placeholder="np. ST_NIS" />

            <flux:select wire:model="form.status" label="Status" required>
                @foreach ($statuses as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="form.purchase_doc_number" label="Numer dokumentu zakupu" class="sm:col-span-2" />

            <flux:textarea wire:model="form.description" label="Opis" rows="2" class="sm:col-span-2" />
            <flux:textarea wire:model="form.comment" label="Komentarz" rows="2" class="sm:col-span-2" />
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ $editing ? 'Zapisz zmiany' : 'Dodaj środek' }}</flux:button>
            <flux:button :href="route('assets.index')" variant="ghost" wire:navigate>Anuluj</flux:button>
        </div>
    </form>

    {{-- Operations on an existing asset: start a transfer or a liquidation. --}}
    @if ($editing)
        @can('create', App\Models\TransferRequest::class)
            <div class="mt-8">
                <flux:heading size="lg">Operacje na środku</flux:heading>
                <flux:subheading class="mb-4">Rozpocznij przekazanie do innego pola spisowego lub wniosek o likwidację. Po utworzeniu środek zostanie zablokowany do czasu rozpatrzenia.</flux:subheading>

                <div class="grid gap-4 lg:grid-cols-2">
                    {{-- Transfer --}}
                    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex items-center gap-2 font-medium">
                            <flux:icon.arrow-right-circle variant="micro" /> Przekaż do innego pola
                        </div>
                        <flux:select wire:model="targetFieldId" label="Pole docelowe">
                            <flux:select.option value="">— wybierz —</flux:select.option>
                            @foreach ($fields as $field)
                                @if ($field->id !== $form->inventory_field_id)
                                    <flux:select.option value="{{ $field->id }}">{{ $field->label() }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="transferNote" label="Notatka" placeholder="opcjonalnie" />
                        <flux:button wire:click="requestTransfer" variant="primary" icon="paper-airplane" class="self-start">
                            Utwórz przekazanie
                        </flux:button>
                    </div>

                    {{-- Liquidation --}}
                    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="flex items-center gap-2 font-medium">
                            <flux:icon.trash variant="micro" /> Zgłoś do likwidacji
                        </div>
                        <flux:textarea wire:model="liquidationNote" label="Uzasadnienie" rows="2" placeholder="np. sprzęt uszkodzony" />
                        <flux:button
                            wire:click="requestLiquidation"
                            wire:confirm="Utworzyć wniosek o likwidację tego środka?"
                            variant="danger"
                            icon="trash"
                            class="self-start"
                        >
                            Wniosek o likwidację
                        </flux:button>
                    </div>
                </div>
            </div>
        @endcan
    @endif
</div>
