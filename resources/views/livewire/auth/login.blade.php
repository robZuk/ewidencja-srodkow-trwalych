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
</div>
