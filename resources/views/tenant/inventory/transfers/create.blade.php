@extends('layouts.admin')

@section('title', __('Create Branch Stock Transfer'))

@section('content')
  <x-breadcrumb 
    :title="__('New Stock Transfer')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Transfers') => route('tenant.inventory.transfers.index'),
      __('New') => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Transfers') }}</span>
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

  <form action="{{ route('tenant.inventory.transfers.store') }}" method="POST" id="transferForm">
    @csrf

    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-8">
        <!-- Branch & Logistic Info Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold text-primary">
              <i class="bi bi-geo-alt me-2"></i>{{ __('Transfer Route & Logistics') }}
            </h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">{{ __('Source Branch (Dispatch From)') }} <span class="text-danger">*</span></label>
                <select name="from_branch_id" class="form-select @error('from_branch_id') is-invalid @enderror" required>
                  <option value="">{{ __('Select Source Branch') }}</option>
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('from_branch_id') == $b->id ? 'selected' : '' }}>
                      {{ $b->name }} {{ $b->is_headquarters ? '(' . __('HQ') . ')' : '' }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">{{ __('Destination Branch (Transfer To)') }} <span class="text-danger">*</span></label>
                <select name="to_branch_id" class="form-select @error('to_branch_id') is-invalid @enderror" required>
                  <option value="">{{ __('Select Destination Branch') }}</option>
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('to_branch_id') == $b->id ? 'selected' : '' }}>
                      {{ $b->name }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Transfer Date') }} <span class="text-danger">*</span></label>
                <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', now()->toDateString()) }}" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Vehicle / Suzuki / Rickshaw #') }}</label>
                <input type="text" name="vehicle_number" class="form-control" placeholder="{{ __('e.g., LEA-1234') }}" value="{{ old('vehicle_number') }}">
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Driver / Messenger Name') }}</label>
                <input type="text" name="driver_name" class="form-control" placeholder="{{ __('e.g., Muhammad Tariq') }}" value="{{ old('driver_name') }}">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">{{ __('Driver Phone') }}</label>
                <input type="text" name="driver_phone" class="form-control" placeholder="{{ __('0300-1234567') }}" value="{{ old('driver_phone') }}">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">{{ __('Notes / Instructions') }}</label>
                <input type="text" name="notes" class="form-control" placeholder="{{ __('Produce transfer details...') }}" value="{{ old('notes') }}">
              </div>
            </div>
          </div>
        </div>

        <!-- Produce Items Table -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0 fw-bold text-success">
              <i class="bi bi-box-seam me-2"></i>{{ __('Produce Items to Transfer') }}
            </h6>
            <button type="button" class="btn btn-outline-success btn-sm" id="addItemRowBtn">
              <i class="bi bi-plus-circle me-1"></i> {{ __('Add Produce Line') }}
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="itemsTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 60%;">{{ __('Produce') }} <span class="text-danger">*</span></th>
                  <th style="width: 30%;">{{ __('Dispatch Quantity') }} <span class="text-danger">*</span></th>
                  <th style="width: 10%;" class="text-center">{{ __('Action') }}</th>
                </tr>
              </thead>
              <tbody id="itemsContainer">
                <tr class="item-row" data-index="0">
                  <td>
                    <select name="items[0][product_id]" class="form-select product-select" required>
                      <option value="">{{ __('Select Produce...') }}</option>
                      @foreach($products as $prod)
                        <option value="{{ $prod->id }}">
                          {{ $prod->name }} {{ $prod->name_ur ? '(' . $prod->name_ur . ')' : '' }} [{{ $prod->stockUnit->short_name ?? 'kg' }}]
                        </option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="number" step="0.001" name="items[0][quantity]" class="form-control" placeholder="0.00" min="0.001" required>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" disabled>
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold text-primary">
              <i class="bi bi-send-check me-2"></i>{{ __('Dispatch Transfer') }}
            </h6>
          </div>
          <div class="card-body">
            <div class="alert alert-light border small text-muted mb-3">
              <i class="bi bi-info-circle text-primary me-1"></i>
              {{ __('Upon dispatching, the specified quantities will be immediately deducted from the source branch and logged as "Transfer Out". Once delivered, the destination branch can acknowledge receipt.') }}
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-check-circle me-1"></i> {{ __('Dispatch Transfer Voucher') }}
            </button>
            <a href="{{ route('tenant.inventory.transfers.index') }}" class="btn btn-outline-secondary w-100 mt-2">
              {{ __('Cancel') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let rowIndex = 1;
      const container = document.getElementById('itemsContainer');
      const addBtn = document.getElementById('addItemRowBtn');

      addBtn.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.setAttribute('data-index', rowIndex);
        row.innerHTML = `
          <td>
            <select name="items[${rowIndex}][product_id]" class="form-select product-select" required>
              <option value="">{{ __('Select Produce...') }}</option>
              @foreach($products as $prod)
                <option value="{{ $prod->id }}">
                  {{ $prod->name }} {{ $prod->name_ur ? '(' . $prod->name_ur . ')' : '' }} [{{ $prod->stockUnit->short_name ?? 'kg' }}]
                </option>
              @endforeach
            </select>
          </td>
          <td>
            <input type="number" step="0.001" name="items[${rowIndex}][quantity]" class="form-control" placeholder="0.00" min="0.001" required>
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        `;
        container.appendChild(row);
        rowIndex++;
        updateRemoveButtons();
      });

      container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row-btn')) {
          const rows = container.querySelectorAll('.item-row');
          if (rows.length > 1) {
            e.target.closest('.item-row').remove();
            updateRemoveButtons();
          }
        }
      });

      function updateRemoveButtons() {
        const rows = container.querySelectorAll('.item-row');
        rows.forEach(r => {
          const btn = r.querySelector('.remove-row-btn');
          if (btn) btn.disabled = (rows.length <= 1);
        });
      }
    });
  </script>
@endsection

