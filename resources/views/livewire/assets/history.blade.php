<?php

use App\Models\Asset;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Historia środka'])] class extends Component
{
    use WithPagination;

    public Asset $asset;

    public function mount(Asset $asset): void
    {
        $this->authorize('view', $asset);

        $this->asset = $asset;
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'activities' => $this->asset->activities()->with('user')->paginate(20),
        ];
    }

    public function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Utworzenie',
            'updated' => 'Zmiana',
            'deleted' => 'Usunięcie',
            default => $event,
        };
    }
}; ?>

<div>
    <div class="mb-6 flex items-center gap-3">
        <flux:button :href="route('assets.index')" variant="subtle" icon="arrow-left" size="sm" wire:navigate>
            Wróć
        </flux:button>
        <div>
            <flux:heading size="xl">Historia: {{ $asset->name }}</flux:heading>
            <flux:subheading>{{ $asset->inventory_number }} · {{ $asset->inventoryField?->label() }}</flux:subheading>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Data</th>
                    <th class="px-4 py-3 font-medium">Zdarzenie</th>
                    <th class="px-4 py-3 font-medium">Pole</th>
                    <th class="px-4 py-3 font-medium">Poprzednia wartość</th>
                    <th class="px-4 py-3 font-medium">Nowa wartość</th>
                    <th class="px-4 py-3 font-medium">Użytkownik</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($activities as $activity)
                    <tr wire:key="activity-{{ $activity->id }}">
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-500">{{ $activity->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $this->eventLabel($activity->event) }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $activity->field ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $activity->old_value ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $activity->new_value ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $activity->user_name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500">Brak zarejestrowanych zmian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
</div>
