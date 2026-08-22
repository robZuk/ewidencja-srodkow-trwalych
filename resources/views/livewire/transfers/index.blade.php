<?php

use App\Actions\Transfers\AcceptLiquidation;
use App\Actions\Transfers\AcceptTransfer;
use App\Actions\Transfers\RejectRequest;
use App\Actions\Transfers\RequestLiquidation;
use App\Actions\Transfers\RequestTransfer;
use App\Enums\TransferType;
use App\Models\Asset;
use App\Models\InventoryField;
use App\Models\TransferRequest;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Powiadomienia'])] class extends Component
{
    use WithPagination;

    public ?int $assetId = null;

    public string $type = 'transfer';

    public ?int $targetFieldId = null;

    public string $note = '';

    public function createRequest(RequestTransfer $requestTransfer, RequestLiquidation $requestLiquidation): void
    {
        $this->authorize('create', TransferRequest::class);

        $this->validate([
            'assetId' => ['required', 'exists:assets,id'],
            'type' => ['required', 'in:transfer,liquidation'],
            'targetFieldId' => ['nullable', 'required_if:type,transfer', 'exists:inventory_fields,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $asset = Asset::findOrFail($this->assetId);
        $user = auth()->user();

        if ($this->type === 'transfer') {
            $requestTransfer->handle($asset, InventoryField::findOrFail($this->targetFieldId), $user, $this->note ?: null);
        } else {
            $requestLiquidation->handle($asset, $user, $this->note ?: null);
        }

        $this->reset('assetId', 'targetFieldId', 'note');
        $this->type = 'transfer';

        session()->flash('status', 'Zgłoszenie zostało utworzone.');
    }

    public function accept(TransferRequest $request, AcceptTransfer $acceptTransfer, AcceptLiquidation $acceptLiquidation): void
    {
        $this->authorize('decide', $request);

        if ($request->type === TransferType::Liquidation) {
            $acceptLiquidation->handle($request, auth()->user());
        } else {
            $acceptTransfer->handle($request, auth()->user());
        }

        session()->flash('status', 'Zgłoszenie zostało zaakceptowane.');
    }

    public function reject(TransferRequest $request, RejectRequest $rejectRequest): void
    {
        $this->authorize('decide', $request);

        $rejectRequest->handle($request, auth()->user());

        session()->flash('status', 'Zgłoszenie zostało odrzucone.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'requests' => TransferRequest::query()
                ->with(['asset', 'sourceField', 'targetField', 'requester'])
                ->latest()
                ->paginate(15),
            'assets' => Asset::query()->where('status', 'available')->orderBy('name')->get(),
            'fields' => InventoryField::query()->orderBy('code')->get(),
            'canDecide' => auth()->user()?->can('decide transfers') ?? false,
            'canRequest' => auth()->user()?->can('request transfers') ?? false,
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl">Powiadomienia</flux:heading>

    @if ($canRequest)
        <form wire:submit="createRequest" class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 lg:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-950">
            <flux:select wire:model="assetId" label="Środek" class="lg:col-span-2">
                <flux:select.option value="">— wybierz środek —</flux:select.option>
                @foreach ($assets as $asset)
                    <flux:select.option value="{{ $asset->id }}">{{ $asset->inventory_number }} · {{ $asset->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="type" label="Typ zgłoszenia">
                <flux:select.option value="transfer">Przekazanie środka</flux:select.option>
                <flux:select.option value="liquidation">Wniosek o likwidację</flux:select.option>
            </flux:select>

            @if ($type === 'transfer')
                <flux:select wire:model="targetFieldId" label="Pole docelowe">
                    <flux:select.option value="">— wybierz —</flux:select.option>
                    @foreach ($fields as $field)
                        <flux:select.option value="{{ $field->id }}">{{ $field->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="note" label="Notatka" placeholder="opcjonalnie" class="{{ $type === 'transfer' ? 'lg:col-span-3' : 'lg:col-span-4' }}" />

            <div class="sm:col-span-2 lg:col-span-4">
                <flux:button type="submit" variant="primary" icon="paper-airplane">Utwórz zgłoszenie</flux:button>
            </div>
        </form>
    @endif

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
                            <div class="flex items-center justify-end gap-1">
                                @if ($canDecide && $request->status->isOpen())
                                    <flux:button wire:click="accept({{ $request->id }})" size="xs" variant="primary" icon="check">Akceptuj</flux:button>
                                    <flux:button wire:click="reject({{ $request->id }})" wire:confirm="Odrzucić zgłoszenie?" size="xs" variant="subtle" icon="x-mark" />
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
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
</div>
