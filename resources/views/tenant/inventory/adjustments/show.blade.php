@extends('layouts.admin')

@section('title', __('Physical Stock Reconciliation') . ' - ' . $stockAdjustment->adjustment_no)

@section('content')
  <x-breadcrumb 
    :title="$stockAdjustment->adjustment_no" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Adjustments') => route('tenant.inventory.adjustments.index'),
      $stockAdjustment->adjustment_no => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Adjustments') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-boxes"></i>
        <span>{{ __('View Current Stock') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-info-circle me-2"></i>{{ __('Audit Information') }}
          </h6>
        </div>
        <div class="card-body">
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>{{ __('Adjustment #:') }}</strong> <span class="font-monospace fw-bold">{{ $stockAdjustment->adjustment_no }}</span></li>
            <li class="mb-2"><strong>{{ __('Date:') }}</strong> {{ $stockAdjustment->date->format('d M, Y') }}</li>
            <li class="mb-2"><strong>{{ __('Branch Outlet:') }}</strong> {{ $stockAdjustment->branch->name ?? '-' }}</li>
            <li class="mb-2">
              <strong>{{ __('Audit Reason:') }}</strong>
              <span class="badge bg-secondary-subtle text-dark border">
                {{ ucfirst(str_replace('_', ' ', $stockAdjustment->reason)) }}
              </span>
            </li>
            <li class="mb-0"><strong>{{ __('Audited By:') }}</strong> {{ $stockAdjustment->creator->name ?? 'Staff' }}</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-journal-text me-2"></i>{{ __('Notes & Audit Remarks') }}
          </h6>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0">
            {{ $stockAdjustment->notes ?? __('No additional remarks noted for this session.') }}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Reconciled Produce Items -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold text-dark">
        <i class="bi bi-clipboard-data me-2 text-warning"></i>{{ __('Audit Reconciliation Variance Details') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>{{ __('Produce Item') }}</th>
            <th class="text-end">{{ __('Previous System Stock') }}</th>
            <th class="text-end">{{ __('Counted Physical Stock') }}</th>
            <th class="text-end">{{ __('Variance (+ / -)') }}</th>
            <th class="text-end">{{ __('Unit Cost') }}</th>
            <th class="text-end pe-3">{{ __('Cost Variance Value') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($stockAdjustment->items as $idx => $item)
            @php
              $diff = (float) $item->difference_quantity;
            @endphp
            <tr>
              <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
              <td>
                <div class="fw-bold">{{ $item->product->name }}</div>
                @if($item->product->name_ur)
                  <div class="text-success small font-urdu">{{ $item->product->name_ur }}</div>
                @endif
              </td>
              <td class="text-end font-monospace text-muted">
                {{ number_format($item->current_quantity, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace fw-bold">
                {{ number_format($item->counted_quantity, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace fw-bold">
                @if($diff > 0)
                  <span class="text-success">+{{ number_format($diff, 2) }}</span>
                @elseif($diff < 0)
                  <span class="text-danger">{{ number_format($diff, 2) }}</span>
                @else
                  <span class="text-muted">0.00</span>
                @endif
              </td>
              <td class="text-end font-monospace small">
                {{ $tenant->currency }} {{ number_format($item->unit_cost, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold pe-3">
                @php $valVariance = $diff * (float) $item->unit_cost; @endphp
                <span class="{{ $valVariance > 0 ? 'text-success' : ($valVariance < 0 ? 'text-danger' : 'text-muted') }}">
                  {{ $valVariance > 0 ? '+' : '' }}{{ $tenant->currency }} {{ number_format($valVariance, 2) }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection
