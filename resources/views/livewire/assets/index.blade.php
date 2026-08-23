<?php

use App\Actions\Transfers\RequestLiquidation;
use App\Actions\Transfers\RequestTransfer;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Środki'])] class extends Component
{
    use WithPagination;

    /** Columns the list may be sorted by (whitelist — $sort comes from the URL). */
    private const SORTABLE = ['inventory_number', 'name', 'value', 'purchase_date', 'status'];

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $field = null;

    #[Url]
    public string $sort = 'inventory_number';

    #[Url]
    public string $direction = 'asc';

    // Quick "operacje na środku" modal (transfer / liquidation) launched from a row.
    public ?int $opsAssetId = null;

    public ?int $opsTargetFieldId = null;

    public string $opsTransferNote = '';

    public string $opsLiquidationNote = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'field'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }
    }

    public function delete(Asset $asset): void
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        $this->dispatch('notify', message: "Środek „{$asset->name}” został usunięty.");
    }

    public function requestTransfer(RequestTransfer $action): void
    {
        $this->authorize('create', TransferRequest::class);

        $asset = Asset::findOrFail($this->opsAssetId);

        $this->validate(
            ['opsTargetFieldId' => ['required', 'exists:inventory_fields,id']],
            attributes: ['opsTargetFieldId' => 'pole docelowe'],
        );

        if ($asset->isLockedForEditing()) {
            $this->addError('opsTargetFieldId', 'Ten środek ma już otwarte zgłoszenie.');

            return;
        }

        if ((int) $this->opsTargetFieldId === (int) $asset->inventory_field_id) {
            $this->addError('opsTargetFieldId', 'Pole docelowe musi być inne niż obecne pole środka.');

            return;
        }

        $action->handle($asset, InventoryField::findOrFail($this->opsTargetFieldId), auth()->user(), $this->opsTransferNote ?: null);

        $this->dispatch('close-asset-ops');
        $this->dispatch('requests-updated');
        $this->dispatch('notify', message: 'Utworzono przekazanie — środek oczekuje na akceptację.');
    }

    public function requestLiquidation(RequestLiquidation $action): void
    {
        $this->authorize('create', TransferRequest::class);

        $asset = Asset::findOrFail($this->opsAssetId);

        if ($asset->isLockedForEditing()) {
            $this->addError('opsLiquidationNote', 'Ten środek ma już otwarte zgłoszenie.');

            return;
        }

        $action->handle($asset, auth()->user(), $this->opsLiquidationNote ?: null);

        $this->dispatch('close-asset-ops');
        $this->dispatch('requests-updated');
        $this->dispatch('notify', message: 'Utworzono wniosek o likwidację — środek oczekuje na akceptację.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'inventory_number';

        return [
            'assets' => Asset::query()
                ->with(['inventoryField', 'location'])
                ->withCount(['transferRequests as open_transfers_count' => fn ($query) => $query->open()])
                ->search($this->search)
                ->withStatus($this->status)
                ->forField($this->field)
                ->orderBy($sort, $this->direction === 'desc' ? 'desc' : 'asc')
                ->paginate(15),
            'fields' => InventoryField::query()->orderBy('code')->get(),
            'statuses' => AssetStatus::cases(),
            'opsAsset' => $this->opsAssetId !== null ? Asset::find($this->opsAssetId) : null,
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Środki jednostki</flux:heading>
            <flux:subheading>Łącznie: {{ number_format($assets->total(), 0, ',', ' ') }}</flux:subheading>
        </div>

        @php($exportParams = ['search' => $search, 'status' => $status, 'field' => $field])
        <div class="flex items-center gap-2">
            <flux:button :href="route('assets.export.csv', $exportParams)" variant="subtle" icon="arrow-down-tray" size="sm">CSV</flux:button>
            <flux:button :href="route('assets.export.xlsx', $exportParams)" variant="subtle" icon="table-cells" size="sm">Excel</flux:button>
            @can('manage assets')
                <flux:button :href="route('assets.create')" variant="primary" icon="plus" wire:navigate>
                    Dodaj środek
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Szukaj: nazwa, numer, opis…"
            class="lg:col-span-2"
        />

        <flux:select wire:model.live="status" placeholder="Wszystkie statusy">
            <flux:select.option value="">Wszystkie statusy</flux:select.option>
            @foreach ($statuses as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="field" placeholder="Wszystkie pola spisowe">
            <flux:select.option value="">Wszystkie pola spisowe</flux:select.option>
            @foreach ($fields as $inventoryField)
                <flux:select.option value="{{ $inventoryField->id }}">{{ $inventoryField->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <x-th column="inventory_number" :sort="$sort" :direction="$direction">Numer inw.</x-th>
                    <x-th column="name" :sort="$sort" :direction="$direction">Nazwa</x-th>
                    <th class="px-4 py-3 font-medium">Pole spisowe</th>
                    <th class="px-4 py-3 font-medium">Lokalizacja</th>
                    <x-th column="value" :sort="$sort" :direction="$direction" class="text-right">Wartość</x-th>
                    <x-th column="status" :sort="$sort" :direction="$direction">Status</x-th>
                    <x-th column="purchase_date" :sort="$sort" :direction="$direction">Data zakupu</x-th>
                    <th class="px-4 py-3 text-right font-medium">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/40" wire:key="asset-{{ $asset->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $asset->inventory_number }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $asset->name }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $asset->inventoryField?->label() }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $asset->location?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $asset->value, 2, ',', ' ') }} zł</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$asset->status->color()" size="sm">{{ $asset->status->label() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $asset->purchase_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button :href="route('assets.history', $asset)" size="xs" variant="subtle" icon="clock" wire:navigate />
                                @can('create', App\Models\TransferRequest::class)
                                    @if ($asset->open_transfers_count === 0)
                                        <flux:button
                                            x-on:click="$wire.set('opsAssetId', {{ $asset->id }}, false); window.Flux.modal('asset-ops').show()"
                                            size="xs"
                                            variant="subtle"
                                            icon="arrows-right-left"
                                            title="Przekaż / zlikwiduj"
                                        />
                                    @endif
                                @endcan
                                @can('manage assets')
                                    @if ($asset->open_transfers_count > 0)
                                        <flux:badge color="amber" size="sm" icon="lock-closed">W akceptacji</flux:badge>
                                    @else
                                        <flux:button :href="route('assets.edit', $asset)" size="xs" variant="subtle" icon="pencil-square" wire:navigate />
                                        <flux:button
                                            wire:click="delete({{ $asset->id }})"
                                            wire:confirm="Czy na pewno usunąć ten środek?"
                                            size="xs"
                                            variant="subtle"
                                            icon="trash"
                                        />
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-zinc-500">
                            Brak środków spełniających kryteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $assets->links() }}
    </div>

    {{-- Quick operations: start a transfer or liquidation straight from the list. --}}
    <flux:modal
        name="asset-ops"
        class="max-w-lg"
        x-on:close-asset-ops.window="window.Flux.modal('asset-ops').close()"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Operacje na środku</flux:heading>
                @if ($opsAsset)
                    <flux:subheading>{{ $opsAsset->inventory_number }} · {{ $opsAsset->name }} ({{ $opsAsset->inventoryField?->label() }})</flux:subheading>
                @endif
            </div>

            {{-- Transfer --}}
            <div class="space-y-3">
                <div class="flex items-center gap-2 font-medium">
                    <flux:icon.arrow-right-circle variant="micro" /> Przekaż do innego pola
                </div>
                <flux:select wire:model="opsTargetFieldId" label="Pole docelowe">
                    <flux:select.option value="">— wybierz —</flux:select.option>
                    @foreach ($fields as $field)
                        @if (! $opsAsset || $field->id !== $opsAsset->inventory_field_id)
                            <flux:select.option value="{{ $field->id }}">{{ $field->label() }}</flux:select.option>
                        @endif
                    @endforeach
                </flux:select>
                <flux:input wire:model="opsTransferNote" label="Notatka" placeholder="opcjonalnie" />
                <flux:button wire:click="requestTransfer" variant="primary" icon="paper-airplane" class="self-start">
                    Utwórz przekazanie
                </flux:button>
            </div>

            <flux:separator />

            {{-- Liquidation --}}
            <div class="space-y-3">
                <div class="flex items-center gap-2 font-medium">
                    <flux:icon.trash variant="micro" /> Zgłoś do likwidacji
                </div>
                <flux:textarea wire:model="opsLiquidationNote" label="Uzasadnienie" rows="2" placeholder="np. sprzęt uszkodzony" />
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
    </flux:modal>
</div>
