<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\InventoryField;
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

        session()->flash('status', "Środek „{$asset->name}” został usunięty.");
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
</div>
