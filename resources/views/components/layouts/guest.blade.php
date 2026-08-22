@props(['title' => 'Logowanie'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="flex h-full items-center justify-center bg-zinc-100 p-6 antialiased dark:bg-zinc-900">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                <flux:icon.archive-box variant="micro" />
            </div>
            <span class="text-xl font-semibold tracking-tight text-zinc-800 dark:text-zinc-100">Asset Inventory</span>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-zinc-400">
            System zarządzania środkami trwałymi · demo portfolio
        </p>
    </div>

    @fluxScripts
</body>
</html>
