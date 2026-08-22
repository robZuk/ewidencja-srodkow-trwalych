<?php

use App\Enums\TransferType;
use App\Models\TransferRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Druki Likwidacji'])] class extends Component
{
    use WithPagination;

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'requests' => TransferRequest::query()
                ->ofType(TransferType::Liquidation)
                ->with(['asset', 'sourceField'])
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Druki Likwidacji</flux:heading>
        <flux:subheading>Protokoły likwidacji środków trwałych.</flux:subheading>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Środek</th>
                    <th class="px-4 py-3 font-medium">Pole spisowe</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Data</th>
                    <th class="px-4 py-3 text-right font-medium">Druk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($requests as $request)
                    <tr wire:key="lik-{{ $request->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—' }}</div>
                            <div class="font-mono text-xs text-zinc-500">{{ $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->sourceField?->label() }}</td>
                        <td class="px-4 py-3"><flux:badge :color="$request->status->color()" size="sm">{{ $request->status->label() }}</flux:badge></td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <flux:button :href="route('druki.likwidacja.pdf', $request)" size="xs" variant="subtle" icon="arrow-down-tray">PDF</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-zinc-500">Brak wniosków o likwidację.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
