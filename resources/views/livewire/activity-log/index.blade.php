<?php

use App\Models\Activity;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\Location;
use App\Models\TransferRequest;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Historia zmian'])] class extends Component
{
    use WithPagination;

    /** @var array<class-string, string> */
    private const TYPES = [
        Asset::class => 'Środek',
        User::class => 'Użytkownik',
        InventoryField::class => 'Pole spisowe',
        Location::class => 'Lokalizacja',
        TransferRequest::class => 'Zgłoszenie',
    ];

    #[Url]
    public string $search = '';

    #[Url]
    public string $event = '';

    #[Url]
    public string $type = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view activity log'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'event', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function eventLabel(string $event): string
    {
        return ['created' => 'Utworzenie', 'updated' => 'Aktualizacja', 'deleted' => 'Usunięcie'][$event] ?? $event;
    }

    public function eventColor(string $event): string
    {
        return ['created' => 'green', 'updated' => 'amber', 'deleted' => 'red'][$event] ?? 'zinc';
    }

    public function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? '—';
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'activities' => Activity::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($query) {
                        $query->where('subject_label', 'like', "%{$this->search}%")
                            ->orWhere('causer_name', 'like', "%{$this->search}%");
                    });
                })
                ->when($this->event !== '', fn ($query) => $query->where('event', $this->event))
                ->when($this->type !== '', fn ($query) => $query->where('subject_type', $this->type))
                ->latest()
                ->paginate(20),
            'types' => self::TYPES,
        ];
    }
}; ?>

<div x-data="{ changes: null, label: '' }">
    <div class="mb-6">
        <flux:heading size="xl">Historia zmian</flux:heading>
        <flux:subheading>Dziennik zmian w systemie: dodawanie, aktualizacja i usuwanie.</flux:subheading>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj: obiekt lub użytkownik…" class="lg:col-span-2" />

        <flux:select wire:model.live="event" placeholder="Wszystkie zdarzenia">
            <flux:select.option value="">Wszystkie zdarzenia</flux:select.option>
            <flux:select.option value="created">Utworzenie</flux:select.option>
            <flux:select.option value="updated">Aktualizacja</flux:select.option>
            <flux:select.option value="deleted">Usunięcie</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="type" placeholder="Wszystkie typy">
            <flux:select.option value="">Wszystkie typy</flux:select.option>
            @foreach ($types as $class => $label)
                <flux:select.option value="{{ $class }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Data</th>
                    <th class="px-4 py-3 font-medium">Zdarzenie</th>
                    <th class="px-4 py-3 font-medium">Typ</th>
                    <th class="px-4 py-3 font-medium">Obiekt</th>
                    <th class="px-4 py-3 font-medium">Użytkownik</th>
                    <th class="px-4 py-3 text-right font-medium">Zmiany</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($activities as $activity)
                    <tr wire:key="activity-{{ $activity->id }}">
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ $activity->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$this->eventColor($activity->event)" size="sm">{{ $this->eventLabel($activity->event) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $this->typeLabel($activity->subject_type) }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $activity->subject_label ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $activity->causer_name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($activity->properties)
                                <flux:button
                                    x-on:click="changes = {{ \Illuminate\Support\Js::from($activity->properties) }}; label = {{ \Illuminate\Support\Js::from($activity->subject_label) }}; window.Flux.modal('activity-changes').show()"
                                    size="xs"
                                    variant="subtle"
                                    icon="eye"
                                    title="Szczegóły zmian"
                                />
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">Brak zarejestrowanych zmian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>

    <flux:modal name="activity-changes" class="max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Szczegóły zmian</flux:heading>
                <flux:subheading x-text="label"></flux:subheading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-zinc-500">
                        <tr>
                            <th class="py-2 pr-3 font-medium">Pole</th>
                            <th class="py-2 pr-3 font-medium">Poprzednia</th>
                            <th class="py-2 font-medium">Nowa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="[field, chg] in Object.entries(changes || {})" :key="field">
                            <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                <td class="py-2 pr-3 font-mono text-xs" x-text="field"></td>
                                <td class="py-2 pr-3 text-zinc-500" x-text="chg.old ?? '—'"></td>
                                <td class="py-2" x-text="chg.new ?? '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </flux:modal>
</div>
