<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    /** @var array<string, string> role => Polish label */
    public const ROLES = [
        'admin' => 'Administrator',
        'editor' => 'Edytor',
        'inventory_section' => 'Sekcja Inwentaryzacji',
        'viewer' => 'Podgląd',
    ];

    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'viewer';

    public string $password = '';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'imię i nazwisko',
            'email' => 'adres e-mail',
            'role' => 'rola',
            'password' => 'hasło',
        ];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->getRoleNames()->first() ?? 'viewer';
    }

    public function store(): User
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password, // hashed by the model cast
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$this->role]);

        return $user;
    }

    public function update(): void
    {
        $this->validate();

        $this->user?->update(array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password !== '' ? $this->password : null,
        ], fn ($value) => $value !== null));

        $this->user?->syncRoles([$this->role]);
    }
}
