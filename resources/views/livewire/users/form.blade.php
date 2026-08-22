<?php

use App\Livewire\Forms\UserForm;
use App\Models\InventoryField;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Użytkownik'])] class extends Component
{
    public UserForm $form;

    public bool $editing = false;

    public function mount(?User $user = null): void
    {
        if ($user !== null && $user->exists) {
            $this->authorize('update', $user);
            $this->editing = true;
            $this->form->setUser($user);
        } else {
            $this->authorize('create', User::class);
        }
    }

    public function save(): void
    {
        if ($this->editing) {
            $this->form->update();
            session()->flash('status', 'Dane użytkownika zostały zaktualizowane.');
        } else {
            $this->form->store();
            session()->flash('status', 'Użytkownik został dodany.');
        }

        $this->redirectRoute('users.index', navigate: true);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'roles' => UserForm::ROLES,
            'fields' => InventoryField::query()->orderBy('code')->get(),
        ];
    }
}; ?>

<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ $editing ? 'Edytuj użytkownika' : 'Nowy użytkownik' }}</flux:heading>
        <flux:subheading>Dane konta i przypisana rola.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-950">
            <flux:input wire:model="form.name" label="Imię i nazwisko" required class="sm:col-span-2" />
            <flux:input wire:model="form.email" type="email" label="Adres e-mail" required class="sm:col-span-2" />

            <flux:select wire:model="form.role" label="Rola" required>
                @foreach ($roles as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="form.password"
                type="password"
                label="Hasło"
                :placeholder="$editing ? 'zostaw puste, aby nie zmieniać' : ''"
                :required="! $editing"
                viewable
            />

            <div class="sm:col-span-2">
                <flux:checkbox.group wire:model="form.fieldIds" label="Pola spisowe użytkownika" description="Użytkownik akceptuje przekazania kierowane do wybranych pól.">
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($fields as $field)
                            <flux:checkbox value="{{ $field->id }}" label="{{ $field->label() }}" />
                        @endforeach
                    </div>
                </flux:checkbox.group>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ $editing ? 'Zapisz zmiany' : 'Dodaj użytkownika' }}</flux:button>
            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>Anuluj</flux:button>
        </div>
    </form>
</div>
