<?php

use App\Livewire\Forms\UserForm;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Użytkownicy'])] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(User $user): void
    {
        $this->authorize('delete', $user);

        $user->delete();

        $this->dispatch('notify', message: "Użytkownik „{$user->name}” został usunięty.");
    }

    public function roleLabel(?string $role): string
    {
        return $role !== null ? (UserForm::ROLES[$role] ?? $role) : '—';
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'users' => User::query()
                ->with('roles')
                ->withCount('inventoryFields')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($query) {
                        $query->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(15),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Użytkownicy</flux:heading>
        @can('create', App\Models\User::class)
            <flux:button :href="route('users.create')" variant="primary" icon="user-plus" wire:navigate>
                Dodaj użytkownika
            </flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Szukaj: imię lub e-mail…" class="mb-4 max-w-md" />

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Imię i nazwisko</th>
                    <th class="px-4 py-3 font-medium">E-mail</th>
                    <th class="px-4 py-3 font-medium">Rola</th>
                    <th class="px-4 py-3 text-right font-medium">Pola spisowe</th>
                    <th class="px-4 py-3 text-right font-medium">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-100">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm">{{ $this->roleLabel($user->getRoleNames()->first()) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $user->inventory_fields_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @can('impersonate', $user)
                                    <flux:button :href="route('impersonate.start', $user)" size="xs" variant="subtle" icon="user-circle">
                                        Przejmij sesję
                                    </flux:button>
                                @endcan
                                @can('update', $user)
                                    <flux:button :href="route('users.edit', $user)" size="xs" variant="subtle" icon="pencil-square" title="Edytuj" wire:navigate />
                                @endcan
                                @can('delete', $user)
                                    <flux:button wire:click="delete({{ $user->id }})" wire:confirm="Usunąć tego użytkownika?" size="xs" variant="subtle" icon="trash" title="Usuń" />
                                @endcan
                                @cannot('update', $user)
                                    @cannot('impersonate', $user)
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endcannot
                                @endcannot
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-zinc-500">Brak użytkowników.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
