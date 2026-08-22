<?php

use App\Models\TransferRequest;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = $this->compute();
    }

    /** Recompute when a request is accepted/rejected anywhere in the app. */
    #[On('requests-updated')]
    public function refresh(): void
    {
        $this->count = $this->compute();
    }

    private function compute(): int
    {
        $user = auth()->user();

        return $user !== null ? TransferRequest::actionableBy($user)->count() : 0;
    }
}; ?>

<span class="ml-auto">
    @if ($count > 0)
        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-semibold text-white">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</span>
