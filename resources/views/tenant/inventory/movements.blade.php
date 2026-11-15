@extends('layouts.admin')

@section('title', __('Stock Movement Ledger & Audit History'))

@section('content')
  <x-breadcrumb 
    :title="__('Stock Movement Ledger')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Movements') => route('tenant.inventory.movements')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Inventory') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.batches') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-layers"></i>
        <span>{{ __('Produce Batches') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.inventory.movements') }}" class="row g-3 align-items-end">
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
          <label class="form-label small fw-semibold text-muted">{{ __('Movement Type') }}</label>
          <select name="type" class="form-select form-select-sm">
            <option value="">{{ __('All Movement Types') }}</option>
            <option value="purchase_in" {{ request('type') == 'purchase_in' ? 'selected' : '' }}>{{ __('Mandi Purchase (Inward)') }}</option>
            <option value="sale_out" {{ request('type') == 'sale_out' ? 'selected' : '' }}>{{ __('POS Sale (Outward)') }}</option>
            <option value="transfer_in" {{ request('type') == 'transfer_in' ? 'selected' : '' }}>{{ __('Transfer Received (Inward)') }}</option>
            <option value="transfer_out" {{ request('type') == 'transfer_out' ? 'selected' : '' }}>{{ __('Transfer Dispatched (Outward)') }}</option>
            <option value="adjustment_in" {{ request('type') == 'adjustment_in' ? 'selected' : '' }}>{{ __('Audit Excess (Inward)') }}</option>
            <option value="adjustment_out" {{ request('type') == 'adjustment_out' ? 'selected' : '' }}>{{ __('Audit Shortage / Loss (Outward)') }}</option>
            <option value="wastage_out" {{ request('type') == 'wastage_out' ? 'selected' : '' }}>{{ __('Wastage & Damage (Outward)') }}</option>
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small fw-semibold text-muted">{{ __('From Date') }}</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small fw-semibold text-muted">{{ __('To Date') }}</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Movements Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-clock-history me-2 text-primary"></i>{{ __('Immutable Stock Movement Entries') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Date & Time') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Produce Item') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Ref / Doc #') }}</th>
            <th class="text-end">{{ __('Before') }}</th>
            <th class="text-end">{{ __('Change') }}</th>
            <th class="text-end">{{ __('After') }}</th>
            <th class="text-end">{{ __('Unit Cost') }}</th>
            <th class="text-end pe-3">{{ __('Logged By') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($movements as $m)
            @php
              $isInward = (float) $m->quantity_change > 0;
            @endphp
            <tr>
              <td class="ps-3 small text-muted">
                {{ $m->created_at->format('d M, Y H:i') }}
              </td>
              <td>
                @if($m->type === 'purchase_in')
                  <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-plus-circle me-1"></i>{{ __('Purchase In') }}</span>
                @elseif($m->type === 'transfer_in')
                  <span class="badge bg-info-subtle text-info border border-info"><i class="bi bi-arrow-down-left me-1"></i>{{ __('Transfer In') }}</span>
                @elseif($m->type === 'transfer_out')
                  <span class="badge bg-warning-subtle text-warning border border-warning"><i class="bi bi-arrow-up-right me-1"></i>{{ __('Transfer Out') }}</span>
                @elseif($m->type === 'adjustment_in')
                  <span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-arrow-up-circle me-1"></i>{{ __('Audit Gain') }}</span>
                @elseif($m->type === 'adjustment_out')
                  <span class="badge bg-danger-subtle text-danger border border-danger"><i class="bi bi-arrow-down-circle me-1"></i>{{ __('Audit Loss') }}</span>
                @elseif($m->type === 'sale_out')
                  <span class="badge bg-secondary"><i class="bi bi-cart me-1"></i>{{ __('Sale Out') }}</span>
                @else
                  <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $m->type)) }}</span>
                @endif
              </td>
              <td>
                <div class="fw-bold">{{ $m->product->name }}</div>
                @if($m->product->name_ur)
                  <div class="text-success small font-urdu">{{ $m->product->name_ur }}</div>
                @endif
              </td>
              <td>
                <span class="small">{{ $m->branch->name ?? '-' }}</span>
              </td>
              <td>
                <span class="font-monospace small fw-bold">{{ $m->reference_no ?? '-' }}</span>
              </td>
              <td class="text-end text-muted font-monospace small">
                {{ number_format($m->quantity_before, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold {{ $isInward ? 'text-success' : 'text-danger' }}">
                {{ $isInward ? '+' : '' }}{{ number_format($m->quantity_change, 2) }} {{ $m->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace fw-semibold">
                {{ number_format($m->quantity_after, 2) }}
              </td>
              <td class="text-end font-monospace small">
                {{ $tenant->currency }} {{ number_format($m->unit_cost, 2) }}
              </td>
              <td class="text-end pe-3 small text-muted">
                {{ $m->creator->name ?? 'System' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                {{ __('No stock movement records found.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($movements->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $movements->links() }}
      </div>
    @endif
  </div>
@endsection
