<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Historia środka'])] class extends Component {}; ?>

<div>
    <flux:heading size="xl">Historia środka</flux:heading>
    <flux:text class="mt-2">Historia zmian środka — w przygotowaniu.</flux:text>
</div>
