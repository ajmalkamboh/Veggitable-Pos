@extends('layouts.admin')

@section('title', __('Current Stock Levels & Inventory Valuation'))

@section('content')
  <x-breadcrumb 
    :title="__('Inventory & Stock')" 
    :items="[
      __('Operations') => '#',
      __('Inventory') => route('tenant.inventory.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.transfers.create') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-right"></i>
        <span>{{ __('Branch Transfer') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.adjustments.create') }}" class="btn btn-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-clipboard-check"></i>
        <span>{{ __('Physical Stock Audit') }}</span>
      </a>
      <a href="{{ route('tenant.purchases.create') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-plus"></i>
        <span>{{ __('Receive Mandi Stock') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($lowStockCount > 0)
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
      <div>
        <strong>{{ __('Low Stock Alert!') }}</strong>
        <div>{{ __(':count produce items are currently running at or below reorder threshold.', ['count' => $lowStockCount]) }}</div>
      </div>
      <a href="{{ route('tenant.inventory.index', ['low_stock_only' => 1]) }}" class="btn btn-outline-dark btn-sm ms-auto">
        {{ __('View Low Stock Items') }}
      </a>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Inventory Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded p-3 fs-3">
            <i class="bi bi-boxes"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Produce Items') }}</span>
            <h4 class="mb-0 fw-bold font-monospace">{{ number_format($totalItems) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success rounded p-3 fs-3">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Stock Valuation') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-success">{{ $tenant->currency }} {{ number_format($totalValuation, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-warning-subtle text-warning rounded p-3 fs-3">
            <i class="bi bi-exclamation-circle"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Low Stock Alerts') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-warning">{{ number_format($lowStockCount) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-danger-subtle text-danger rounded p-3 fs-3">
            <i class="bi bi-slash-circle"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Out of Stock') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-danger">{{ number_format($outOfStockCount) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters & Search -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.inventory.index') }}" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Branch Outlet') }}</label>
          <select name="branch_id" class="form-select form-select-sm">
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                {{ $b->name }} {{ $b->is_headquarters ? '(' . __('HQ') . ')' : '' }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Category') }}</label>
          <select name="category_id" class="form-select form-select-sm">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }} {{ $cat->name_ur ? '(' . $cat->name_ur . ')' : '' }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Search Produce') }}</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Name, Urdu, SKU or Barcode') }}" value="{{ request('search') }}">
        </div>

        <div class="col-12 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Reset Filters') }}">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Stock Inventory Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold text-dark">
        <i class="bi bi-list-check me-2 text-primary"></i>{{ __('Stock Quantities & Live Valuation') }}
      </h6>
      <div class="d-flex gap-2">
        <a href="{{ route('tenant.inventory.batches') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-layers me-1"></i> {{ __('Produce Batches / Lots') }}
        </a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-journal-text me-1"></i> {{ __('Stock Movement Ledger') }}
        </a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>{{ __('Produce Item') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Branch') }}</th>
            <th class="text-end">{{ __('Current Stock') }}</th>
            <th class="text-end">{{ __('Cost Price') }}</th>
            <th class="text-end">{{ __('Stock Valuation') }}</th>
            <th class="text-center">{{ __('Reorder Level') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($stocks as $index => $stock)
            @php
              $threshold = $stock->reorder_level > 0 ? $stock->reorder_level : ($stock->product->reorder_level ?? 0);
              $isLow = (float) $stock->quantity <= (float) $threshold;
              $isOut = (float) $stock->quantity <= 0;
            @endphp
            <tr>
              <td class="ps-3 text-muted">{{ $stocks->firstItem() + $index }}</td>
              <td>
                <div class="fw-bold">{{ $stock->product->name }}</div>
                @if($stock->product->name_ur)
                  <div class="text-success small font-urdu">{{ $stock->product->name_ur }}</div>
                @endif
                <div class="text-muted extra-small font-monospace">{{ $stock->product->code }}</div>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $stock->product->category->name ?? '-' }}</span>
              </td>
              <td>
                <span class="small">{{ $stock->branch->name ?? '-' }}</span>
              </td>
              <td class="text-end">
                <span class="fs-6 fw-bold font-monospace {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : 'text-success') }}">
                  {{ number_format($stock->quantity, 2) }}
                </span>
                <span class="text-muted small">{{ $stock->product->stockUnit->short_name ?? 'kg' }}</span>
              </td>
              <td class="text-end font-monospace">
                {{ $tenant->currency }} {{ number_format($stock->cost_price, 2) }}
              </td>
              <td class="text-end font-monospace fw-semibold text-primary">
                {{ $tenant->currency }} {{ number_format($stock->quantity * $stock->cost_price, 2) }}
              </td>
              <td class="text-center font-monospace text-muted small">
                {{ number_format($threshold, 2) }} {{ $stock->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-center">
                @if($isOut)
                  <span class="badge bg-danger">{{ __('Out of Stock') }}</span>
                @elseif($isLow)
                  <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('Low Stock') }}</span>
                @else
                  <span class="badge bg-success-subtle text-success border border-success">{{ __('In Stock') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tenant.inventory.batches', ['product_id' => $stock->product_id, 'branch_id' => $stock->branch_id]) }}" class="btn btn-outline-secondary" title="{{ __('View Batches / Lots') }}">
                    <i class="bi bi-layers"></i>
                  </a>
                  <a href="{{ route('tenant.inventory.movements', ['product_id' => $stock->product_id, 'branch_id' => $stock->branch_id]) }}" class="btn btn-outline-secondary" title="{{ __('View Movements') }}">
                    <i class="bi bi-clock-history"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                {{ __('No inventory records found for this branch. Stock is automatically established upon Mandi purchase receiving.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($stocks->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $stocks->links() }}
      </div>
    @endif
  </div>
@endsection

