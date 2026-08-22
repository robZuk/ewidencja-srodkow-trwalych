<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Środki'])] class extends Component {}; ?>

<div>
    <flux:heading size="xl">Środki jednostki</flux:heading>
    <flux:text class="mt-2">Lista środków trwałych — pełny widok w przygotowaniu.</flux:text>
</div>
