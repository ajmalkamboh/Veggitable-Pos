@extends('layouts.admin')

@section('title', __('Produce Lots & Batches'))

@section('content')
  <x-breadcrumb 
    :title="__('Produce Lots & Traceability')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Batches & Lots') => route('tenant.inventory.batches')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Inventory') }}</span>
      </a>
      <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-plus"></i>
        <span>{{ __('New Mandi Lot') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.inventory.batches') }}" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Branch Outlet') }}</label>
          <select name="branch_id" class="form-select form-select-sm">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Batch Status') }}</label>
          <select name="status" class="form-select form-select-sm">
            <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('Active (Available Stock)') }}</option>
            <option value="depleted" {{ request('status') == 'depleted' ? 'selected' : '' }}>{{ __('Depleted (Fully Sold/Used)') }}</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label small fw-semibold text-muted">{{ __('Search Batch / Produce') }}</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Lot #, Produce name...') }}" value="{{ request('search') }}">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.inventory.batches') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Batches Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-layers me-2 text-primary"></i>{{ __('Produce Lots & FIFO Traceability') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Lot Number') }}</th>
            <th>{{ __('Produce') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Received Date') }}</th>
            <th>{{ __('Expiry / Shelf-Life') }}</th>
            <th class="text-end">{{ __('Initial Qty') }}</th>
            <th class="text-end">{{ __('Remaining Qty') }}</th>
            <th class="text-end">{{ __('Cost Rate') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Mandi Ref') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($batches as $batch)
            @php
              $isExpired = $batch->expiry_date && $batch->expiry_date->isPast();
              $isExpiringSoon = $batch->expiry_date && ! $isExpired && $batch->expiry_date->diffInDays(now()) <= 2;
            @endphp
            <tr>
              <td class="ps-3 font-monospace fw-bold text-primary">
                {{ $batch->lot_number }}
              </td>
              <td>
                <div class="fw-bold">{{ $batch->product->name }}</div>
                @if($batch->product->name_ur)
                  <div class="text-success small font-urdu">{{ $batch->product->name_ur }}</div>
                @endif
              </td>
              <td>
                <span class="small">{{ $batch->branch->name ?? '-' }}</span>
              </td>
              <td>
                {{ $batch->received_date->format('d M, Y') }}
              </td>
              <td>
                @if($batch->expiry_date)
                  <span class="{{ $isExpired ? 'text-danger fw-bold' : ($isExpiringSoon ? 'text-warning fw-semibold' : 'text-muted') }}">
                    {{ $batch->expiry_date->format('d M, Y') }}
                    @if($isExpired)
                      <span class="badge bg-danger ms-1">{{ __('Expired') }}</span>
                    @elseif($isExpiringSoon)
                      <span class="badge bg-warning text-dark ms-1">{{ __('Expiring Soon') }}</span>
                    @endif
                  </span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td class="text-end text-muted font-monospace">
                {{ number_format($batch->initial_quantity, 2) }} {{ $batch->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace fw-bold {{ (float) $batch->current_quantity > 0 ? 'text-success' : 'text-muted' }}">
                {{ number_format($batch->current_quantity, 2) }} {{ $batch->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace">
                {{ $tenant->currency }} {{ number_format($batch->unit_cost, 2) }}
              </td>
              <td class="text-center">
                @if($batch->status === 'active')
                  <span class="badge bg-success">{{ __('Active') }}</span>
                @elseif($batch->status === 'depleted')
                  <span class="badge bg-secondary">{{ __('Depleted') }}</span>
                @else
                  <span class="badge bg-danger">{{ ucfirst($batch->status) }}</span>
                @endif
              </td>
              <td class="text-end pe-3 small">
                @if($batch->purchaseInvoice)
                  <a href="{{ route('tenant.purchases.show', $batch->purchaseInvoice) }}" class="text-decoration-none">
                    {{ $batch->purchaseInvoice->invoice_no }}
                  </a>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                {{ __('No produce batches found matching criteria.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($batches->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $batches->links() }}
      </div>
    @endif
  </div>
@endsection
