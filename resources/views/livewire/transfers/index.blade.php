<?php

use App\Actions\Transfers\AcceptLiquidation;
use App\Actions\Transfers\AcceptTransfer;
use App\Actions\Transfers\RejectRequest;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\TransferRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Powiadomienia'])] class extends Component
{
    use WithPagination;

    public function accept(TransferRequest $request, AcceptTransfer $acceptTransfer, AcceptLiquidation $acceptLiquidation): void
    {
        // Step 1 (Pending) is the target field's call; step 2 the inventory section's.
        $this->authorize(
            $request->status === TransferStatus::Pending ? 'acceptTarget' : 'acceptInventory',
            $request,
        );

        if ($request->type === TransferType::Liquidation) {
            $acceptLiquidation->handle($request, auth()->user());
        } else {
            $acceptTransfer->handle($request, auth()->user());
        }

        $this->dispatch('requests-updated');
        $this->dispatch('notify', message: 'Zgłoszenie zostało zaakceptowane.');
    }

    public function reject(TransferRequest $request, RejectRequest $rejectRequest): void
    {
        $this->authorize('reject', $request);

        $rejectRequest->handle($request, auth()->user());

        $this->dispatch('requests-updated');
        $this->dispatch('notify', message: 'Zgłoszenie zostało odrzucone.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'requests' => TransferRequest::query()
                ->with(['asset', 'sourceField', 'targetField', 'requester'])
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6" x-data="{ details: null }">
    <div>
        <flux:heading size="xl">Powiadomienia</flux:heading>
        <flux:subheading>Zgłoszenia przekazań i likwidacji do akceptacji. Nowe zgłoszenie rozpoczniesz z poziomu edycji środka.</flux:subheading>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Typ</th>
                    <th class="px-4 py-3 font-medium">Środek</th>
                    <th class="px-4 py-3 font-medium">Przepływ</th>
                    <th class="px-4 py-3 font-medium">Zgłaszający</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($requests as $request)
                    <tr wire:key="request-{{ $request->id }}">
                        <td class="px-4 py-3">
                            <flux:badge :color="$request->type === App\Enums\TransferType::Liquidation ? 'red' : 'blue'" size="sm">
                                {{ $request->type->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—' }}</div>
                            <div class="font-mono text-xs text-zinc-500">{{ $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $request->sourceField?->code }}
                            @if ($request->targetField)
                                <flux:icon.arrow-right variant="micro" class="mx-1 inline size-3" /> {{ $request->targetField->code }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->requester?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$request->status->color()" size="sm">{{ $request->status->label() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            @php($canAct = $request->status->isOpen() && auth()->user()->can(
                                $request->status === App\Enums\TransferStatus::Pending ? 'acceptTarget' : 'acceptInventory',
                                $request,
                            ))
                            @php($detail = [
                                'type' => $request->type->label(),
                                'status' => $request->status->label(),
                                'asset' => $request->asset?->name ?? $request->asset_snapshot['name'] ?? '—',
                                'inventory_number' => $request->asset?->inventory_number ?? $request->asset_snapshot['inventory_number'] ?? '',
                                'source' => $request->sourceField?->label() ?? '—',
                                'target' => $request->targetField?->label() ?? '—',
                                'requester' => $request->requester?->name ?? '—',
                                'date' => $request->created_at?->format('Y-m-d H:i') ?? '',
                                'note' => $request->note ?: '—',
                            ])
                            <div class="flex items-center justify-end gap-1">
                                <flux:button
                                    x-on:click="details = {{ \Illuminate\Support\Js::from($detail) }}; window.Flux.modal('request-details').show()"
                                    size="xs"
                                    variant="subtle"
                                    icon="eye"
                                    title="Szczegóły"
                                />
                                @if ($canAct)
                                    <flux:button wire:click="accept({{ $request->id }})" size="xs" variant="primary" icon="check">Akceptuj</flux:button>
                                    <flux:button wire:click="reject({{ $request->id }})" wire:confirm="Odrzucić zgłoszenie?" size="xs" variant="subtle" icon="x-mark" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500">Brak zgłoszeń.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $requests->links() }}</div>

    <flux:modal name="request-details" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Szczegóły zgłoszenia</flux:heading>

            <dl class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                <dt class="text-zinc-500">Typ</dt>
                <dd class="col-span-2" x-text="details?.type"></dd>

                <dt class="text-zinc-500">Status</dt>
                <dd class="col-span-2" x-text="details?.status"></dd>

                <dt class="text-zinc-500">Środek</dt>
                <dd class="col-span-2">
                    <span x-text="details?.asset"></span>
                    <span class="ml-1 font-mono text-xs text-zinc-500" x-text="details?.inventory_number"></span>
                </dd>

                <dt class="text-zinc-500">Pole źródłowe</dt>
                <dd class="col-span-2" x-text="details?.source"></dd>

                <dt class="text-zinc-500">Pole docelowe</dt>
                <dd class="col-span-2" x-text="details?.target"></dd>

                <dt class="text-zinc-500">Zgłaszający</dt>
                <dd class="col-span-2" x-text="details?.requester"></dd>

                <dt class="text-zinc-500">Data zgłoszenia</dt>
                <dd class="col-span-2" x-text="details?.date"></dd>

                <dt class="text-zinc-500">Notatka</dt>
                <dd class="col-span-2" x-text="details?.note"></dd>
            </dl>
        </div>
    </flux:modal>
</div>
