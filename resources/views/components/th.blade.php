@props(['column', 'sort', 'direction'])

@php($active = $sort === $column)

<th {{ $attributes->merge(['class' => 'px-4 py-3 font-medium']) }}>
    <button
        type="button"
        wire:click="sortBy('{{ $column }}')"
        class="inline-flex items-center gap-1 transition hover:text-zinc-700 dark:hover:text-zinc-200"
    >
        {{ $slot }}
        @if ($active)
            <flux:icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" variant="micro" class="size-3" />
        @else
            <flux:icon name="chevron-up-down" variant="micro" class="size-3 text-zinc-300 dark:text-zinc-600" />
        @endif
    </button>
</th>
