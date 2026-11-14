<?php

use Livewire\Component;

new class extends Component
{
    public int $counter = 1;
    public string $tenantSample = 'Al-Madina Vegetables';
    public string $branchSample = 'Main Market Branch';
    public bool $isIsolated = true;

    public function increment(): void
    {
        $this->counter++;
    }

    public function decrement(): void
    {
        if ($this->counter > 1) {
            $this->counter--;
        }
    }

    public function toggleIsolation(): void
    {
        $this->isIsolated = !$this->isIsolated;
    }
};
?>

<div class="livewire-test-container">
    <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded border">
        <span class="small fw-semibold text-muted">Reactive Livewire Test Counter:</span>
        <div class="d-flex align-items-center gap-2">
            <button wire:click="decrement" class="btn btn-outline-secondary btn-sm px-2 py-0">-</button>
            <span class="badge bg-primary fs-6">{{ $counter }}</span>
            <button wire:click="increment" class="btn btn-outline-secondary btn-sm px-2 py-0">+</button>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small text-muted mb-1">Simulated Active Tenant:</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-shop"></i></span>
            <input type="text" wire:model.live="tenantSample" class="form-control form-control-sm">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small text-muted mb-1">Simulated Branch:</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" wire:model.live="branchSample" class="form-control form-control-sm">
        </div>
    </div>

    <div class="p-2 rounded border bg-light d-flex align-items-center justify-content-between">
        <div>
            <div class="small fw-semibold">Tenant Data Isolation Scope:</div>
            <span class="badge {{ $isIsolated ? 'bg-success' : 'bg-warning text-dark' }} small">
                {{ $isIsolated ? 'STRICT ENFORCEMENT' : 'PERMISSIVE TESTING' }}
            </span>
        </div>
        <button wire:click="toggleIsolation" class="btn btn-sm btn-outline-dark">
            Toggle
        </button>
    </div>
</div>
