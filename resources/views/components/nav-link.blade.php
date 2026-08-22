@props(['active' => false, 'icon' => null])

<a {{ $attributes->merge(['href' => '#']) }}
    @class([
        'flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition',
        'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' => $active,
        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $active,
    ])
>
    @if ($icon)
        <flux:icon :name="$icon" variant="outline" class="size-5 shrink-0" />
    @endif
    <span>{{ $slot }}</span>
</a>
