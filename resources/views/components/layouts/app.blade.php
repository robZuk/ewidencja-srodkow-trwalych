@props(['title' => 'Asset Inventory'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="h-full bg-zinc-50 text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-200">
    @if (session()->has('impersonator_id'))
        <div class="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950">
            <flux:icon.user-circle variant="micro" />
            Działasz jako <strong>{{ auth()->user()?->name }}</strong> (przejęcie sesji).
            <a href="{{ route('impersonate.stop') }}" class="underline underline-offset-2 hover:no-underline">
                Zakończ przejęcie
            </a>
        </div>
    @endif

    <div x-data="{ open: false }" class="min-h-full lg:flex">
        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-zinc-200 bg-white transition-transform lg:static lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-950"
            :class="open && 'translate-x-0'"
        >
            <div class="flex h-16 items-center gap-2 border-b border-zinc-200 px-6 dark:border-zinc-800">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <flux:icon.archive-box variant="micro" />
                </div>
                <span class="text-lg font-semibold tracking-tight">Asset Inventory</span>
            </div>

            <nav class="flex flex-col gap-1 p-4 text-sm">
                <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')" icon="squares-2x2">
                    Środki
                </x-nav-link>
                @php($transfersActive = request()->routeIs('transfers.*'))
                <a
                    href="{{ route('transfers.index') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition',
                        'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' => $transfersActive,
                        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $transfersActive,
                    ])
                >
                    <flux:icon name="bell-alert" variant="outline" class="size-5 shrink-0" />
                    <span>Powiadomienia</span>
                    <livewire:notifications-badge />
                </a>
                @cannot('view inventory fields')
                    <x-nav-link :href="route('my-fields')" :active="request()->routeIs('my-fields')" icon="rectangle-stack">
                        Moje pola spisowe
                    </x-nav-link>
                @endcannot

                @can('decide transfers')
                    <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Inwentaryzacja</div>
                    <x-nav-link :href="route('druki.zmu')" :active="request()->routeIs('druki.zmu')" icon="document-text">
                        Druki ZMU
                    </x-nav-link>
                    <x-nav-link :href="route('druki.likwidacja')" :active="request()->routeIs('druki.likwidacja')" icon="trash">
                        Druki Likwidacji
                    </x-nav-link>
                @endcan

                @canany(['view inventory fields', 'manage users'])
                    <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400">Administracja</div>
                    @can('view inventory fields')
                        <x-nav-link :href="route('inventory-fields.index')" :active="request()->routeIs('inventory-fields.index') || request()->routeIs('inventory-fields.create') || request()->routeIs('inventory-fields.edit')" icon="rectangle-stack">
                            Pola Spisowe
                        </x-nav-link>
                    @endcan
                    @can('manage users')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">
                            Użytkownicy
                        </x-nav-link>
                    @endcan
                @endcanany
            </nav>
        </aside>

        {{-- Backdrop for mobile --}}
        <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center gap-4 border-b border-zinc-200 bg-white px-4 lg:px-8 dark:border-zinc-800 dark:bg-zinc-950">
                <button @click="open = !open" class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800">
                    <flux:icon.bars-3 variant="outline" />
                </button>

                <h1 class="truncate text-base font-semibold">{{ $title }}</h1>

                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden text-sm text-zinc-500 sm:block">
                        {{ auth()->user()?->name }}
                        <span class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800">
                            {{ auth()->user()?->getRoleNames()->first() }}
                        </span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button type="submit" size="sm" variant="subtle" icon="arrow-right-start-on-rectangle">
                            Wyloguj
                        </flux:button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <flux:toast position="top right" />
    @fluxScripts
</body>
</html>
