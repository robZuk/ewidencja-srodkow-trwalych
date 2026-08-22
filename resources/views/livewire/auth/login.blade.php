<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Nieprawidłowy adres e-mail lub hasło.',
            ]);
        }

        session()->regenerate();

        $this->redirectIntended(route('assets.index'), navigate: true);
    }

    /** Prefill the read-only demo account for quick evaluation. */
    public function fillDemo(): void
    {
        $this->email = 'demo@example.com';
        $this->password = 'password';
    }
}; ?>

<div>
    <flux:heading size="lg">Zaloguj się</flux:heading>
    <flux:subheading class="mb-6">Wprowadź dane logowania, aby kontynuować.</flux:subheading>

    <form wire:submit="login" class="flex flex-col gap-4">
        <flux:input
            wire:model="email"
            type="email"
            label="Adres e-mail"
            placeholder="ty@example.com"
            autocomplete="username"
        />

        <flux:input
            wire:model="password"
            type="password"
            label="Hasło"
            placeholder="••••••••"
            autocomplete="current-password"
            viewable
        />

        <flux:checkbox wire:model="remember" label="Zapamiętaj mnie" />

        <flux:button type="submit" variant="primary" class="w-full">Zaloguj się</flux:button>
    </form>

    <div class="mt-6 rounded-lg border border-dashed border-zinc-300 p-4 text-sm dark:border-zinc-700">
        <div class="mb-2 flex items-center justify-between">
            <span class="font-medium text-zinc-600 dark:text-zinc-300">Konto demo (tylko podgląd)</span>
            <flux:button wire:click="fillDemo" size="xs" variant="subtle">Wypełnij</flux:button>
        </div>
        <p class="text-zinc-500">
            <code>demo@example.com</code> · hasło <code>password</code>
        </p>
        <p class="mt-1 text-xs text-zinc-400">
            Konta z uprawnieniami: admin@ · editor@ · inwentaryzacja@ (hasło: <code>password</code>)
        </p>
    </div>
</div>
