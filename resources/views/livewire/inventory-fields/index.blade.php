<?php

use App\Models\InventoryField;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Pola Spisowe'])] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', InventoryField::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(InventoryField $inventoryField): void
    {
        $this->authorize('delete', $inventoryField);

        $inventoryField->members()->detach();
        $inventoryField->delete();

        session()->flash('status', "Pole spisowe „{$inventoryField->name}” zostało usunięte.");
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'fields' => InventoryField::query()
                ->withCount(['assets', 'members'])
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('code', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('code')
                ->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Pola spisowe</flux:heading>
            <flux:subheading>Jednostki organizacyjne, do których przypisane są środki.</flux:subheading>
        </div>
        @can('create', App\Models\InventoryField::class)
            <flux:button :href="route('inventory-fields.create')" variant="primary" icon="plus" wire:navigate>
                Dodaj pole
            </flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj: kod lub nazwa…" class="mb-4 max-w-md" />

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Kod</th>
                    <th class="px-4 py-3 font-medium">Nazwa</th>
                    <th class="px-4 py-3 text-right font-medium">Środki</th>
                    <th class="px-4 py-3 text-right font-medium">Członkowie</th>
                    <th class="px-4 py-3 text-right font-medium">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($fields as $field)
                    <tr wire:key="field-{{ $field->id }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $field->code }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $field->name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $field->assets_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $field->members_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @can('update', $field)
                                    <flux:button :href="route('inventory-fields.edit', $field)" size="xs" variant="subtle" icon="pencil-square" wire:navigate />
                                    @can('delete', $field)
                                        <flux:button wire:click="delete({{ $field->id }})" wire:confirm="Usunąć to pole spisowe?" size="xs" variant="subtle" icon="trash" />
                                    @else
                                        <flux:tooltip content="Nie można usunąć — pole ma przypisane środki">
                                            <flux:button size="xs" variant="subtle" icon="trash" disabled />
                                        </flux:tooltip>
                                    @endcan
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-zinc-500">Brak pól spisowych.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $fields->links() }}</div>
</div>
