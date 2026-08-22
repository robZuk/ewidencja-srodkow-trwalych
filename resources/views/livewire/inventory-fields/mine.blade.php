<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Moje pola spisowe'])] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'fields' => auth()->user()->inventoryFields()->orderBy('code')->get(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Moje pola spisowe</flux:heading>
        <flux:subheading>Pola spisowe, do których masz dostęp. Zarządza nimi administrator.</flux:subheading>
    </div>

    @if ($fields->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-700">
            Nie należysz jeszcze do żadnego pola spisowego. Skontaktuj się z administratorem.
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($fields as $field)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm">{{ $field->code }}</flux:badge>
                        <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $field->name }}</span>
                    </div>
                    @if ($field->description)
                        <p class="mt-2 text-sm text-zinc-500">{{ $field->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
