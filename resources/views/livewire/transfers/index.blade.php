<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Powiadomienia'])] class extends Component {}; ?>

<div>
    <flux:heading size="xl">Powiadomienia</flux:heading>
    <flux:text class="mt-2">Powiadomienia o przekazaniach i likwidacjach — w przygotowaniu.</flux:text>
</div>
