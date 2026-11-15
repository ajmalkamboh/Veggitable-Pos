@extends('layouts.admin')

@section('title', __('Physical Stock Audit & Count Reconciliation'))

@section('content')
  <x-breadcrumb 
    :title="__('Physical Stock Audit')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Adjustments') => route('tenant.inventory.adjustments.index'),
      __('New Audit') => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.adjustments.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Adjustments') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Please resolve the following errors:') }}</h6>
      <ul class="mb-0 small">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('tenant.inventory.adjustments.store') }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold text-primary">
          <i class="bi bi-clipboard-check me-2"></i>{{ __('Audit Session Details') }}
        </h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">{{ __('Branch Outlet') }} <span class="text-danger">*</span></label>
            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" onchange="window.location.href='{{ route('tenant.inventory.adjustments.create') }}?branch_id=' + this.value" required>
              @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                  {{ $b->name }}
                </option>
              @endforeach
            </select>
            <small class="text-muted extra-small">{{ __('Switching branch reloads current recorded stock.') }}</small>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">{{ __('Audit Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">{{ __('Adjustment Reason') }} <span class="text-danger">*</span></label>
            <select name="reason" class="form-select" required>
              <option value="physical_count_discrepancy">{{ __('Regular Physical Count Discrepancy') }}</option>
              <option value="drying_weight_loss">{{ __('Produce Evaporation / Drying Weight Loss') }}</option>
              <option value="damage_spoilage">{{ __('Sorting Spoilage & Handling Loss') }}</option>
              <option value="excess_produce_found">{{ __('Excess Produce Found in Cold Store') }}</option>
              <option value="other">{{ __('Other Administrative Reconciliation') }}</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label small fw-semibold text-muted">{{ __('Audit Remarks / Notes') }}</label>
            <input type="text" name="notes" class="form-control" placeholder="{{ __('Optional notes regarding physical stock count...') }}" value="{{ old('notes') }}">
          </div>
        </div>
      </div>
    </div>

    <!-- Product Reconciliation Table -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold text-success">
          <i class="bi bi-boxes me-2"></i>{{ __('Physical Count vs Recorded System Stock') }}
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 5%;" class="ps-3">#</th>
              <th style="width: 40%;">{{ __('Produce') }}</th>
              <th style="width: 15%;" class="text-end">{{ __('System Stock') }}</th>
              <th style="width: 20%;">{{ __('Physical Count') }} <span class="text-danger">*</span></th>
              <th style="width: 20%;" class="text-end pe-3">{{ __('Variance (+ / -)') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($products as $idx => $prod)
              @php
                $currentQty = (float) ($stocks[$prod->id] ?? 0.000);
              @endphp
              <tr class="audit-row" data-current="{{ $currentQty }}">
                <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                <td>
                  <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                  <div class="fw-bold">{{ $prod->name }}</div>
                  @if($prod->name_ur)
                    <div class="text-success small font-urdu">{{ $prod->name_ur }}</div>
                  @endif
                  <div class="text-muted extra-small font-monospace">{{ $prod->code }}</div>
                </td>
                <td class="text-end font-monospace fw-semibold text-muted">
                  {{ number_format($currentQty, 2) }} {{ $prod->stockUnit->short_name ?? 'kg' }}
                </td>
                <td>
                  <div class="input-group input-group-sm">
                    <input type="number" step="0.001" name="items[{{ $idx }}][counted_quantity]" class="form-control counted-input font-monospace" value="{{ old('items.'.$idx.'.counted_quantity', $currentQty) }}" min="0" required>
                    <span class="input-group-text">{{ $prod->stockUnit->short_name ?? 'kg' }}</span>
                  </div>
                </td>
                <td class="text-end font-monospace fw-bold pe-3 variance-cell">
                  0.00
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small">
          * {{ __('Only produce lines where physical count differs from system stock will generate stock movement audit entries.') }}
        </span>
        <button type="submit" class="btn btn-warning px-4 fw-semibold">
          <i class="bi bi-check2-circle me-1"></i> {{ __('Post Physical Stock Reconciliation') }}
        </button>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      function calculateRowVariance(row) {
        const current = parseFloat(row.getAttribute('data-current')) || 0;
        const input = row.querySelector('.counted-input');
        const counted = parseFloat(input.value) || 0;
        const diff = counted - current;
        const cell = row.querySelector('.variance-cell');

        if (diff > 0) {
          cell.innerHTML = `<span class="text-success">+${diff.toFixed(2)}</span>`;
        } else if (diff < 0) {
          cell.innerHTML = `<span class="text-danger">${diff.toFixed(2)}</span>`;
        } else {
          cell.innerHTML = `<span class="text-muted">0.00</span>`;
        }
      }

      document.querySelectorAll('.audit-row').forEach(row => {
        calculateRowVariance(row);
        const input = row.querySelector('.counted-input');
        input.addEventListener('input', () => calculateRowVariance(row));
      });
    });
  </script>
@endsection
