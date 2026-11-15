@extends('layouts.admin')

@section('title', __('New Produce Sorting & Regrading Session'))

@section('content')
  <x-breadcrumb 
    :title="__('New Produce Regrading')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Regradings') => route('tenant.inventory.regradings.index'),
      __('New Session') => route('tenant.inventory.regradings.create')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.regradings.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Cancel') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Please correct the following errors:') }}</div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('tenant.inventory.regradings.store') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-shuffle me-2 text-primary"></i>{{ __('Sorting & Grading Session Header') }}
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold required">{{ __('Branch Outlet') }}</label>
            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
              @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id', $branchId) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold required">{{ __('Date') }}</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <!-- Source Product (Input) -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-box-arrow-up-right me-2 text-warning"></i>{{ __('Source Produce (Before Sorting)') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Source Product') }}</label>
              <select name="source_product_id" id="sourceProduct" class="form-select" required>
                <option value="">{{ __('Select Source Product...') }}</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" {{ old('source_product_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->code }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Input Quantity (kg)') }}</label>
              <input type="number" step="0.001" min="0.001" name="source_quantity" id="sourceQty" class="form-control" value="{{ old('source_quantity') }}" placeholder="0.000" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Source Unit Cost (Rs/kg)') }}</label>
              <input type="number" step="0.01" min="0" name="source_unit_cost" id="sourceCost" class="form-control" value="{{ old('source_unit_cost') }}" placeholder="0.00">
            </div>
          </div>
        </div>
      </div>

      <!-- Target Product (Output) -->
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-box-arrow-in-down-left me-2 text-success"></i>{{ __('Target Produce (After Grading)') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Target Graded Product') }}</label>
              <select name="target_product_id" id="targetProduct" class="form-select" required>
                <option value="">{{ __('Select Target Product...') }}</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" {{ old('target_product_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->code }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Target Yield Quantity (kg)') }}</label>
              <input type="number" step="0.001" min="0.001" name="target_quantity" id="targetQty" class="form-control" value="{{ old('target_quantity') }}" placeholder="0.000" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Target Unit Cost (Rs/kg)') }}</label>
              <input type="number" step="0.01" min="0" name="target_unit_cost" id="targetCost" class="form-control" value="{{ old('target_unit_cost') }}" placeholder="0.00">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Shrinkage & Loss Card -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-md-4">
            <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Sorting Cull / Weight Loss') }}</span>
            <h4 class="fw-bold text-danger mb-0" id="lossQtyDisplay">0.00 kg</h4>
          </div>
          <div class="col-md-4">
            <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Financial Cull Loss (Rs)') }}</span>
            <h4 class="fw-bold text-danger mb-0" id="lossCostDisplay">Rs. 0.00</h4>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Notes') }}</label>
            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('e.g., Morning grading culled rot & soft pieces...') }}" value="{{ old('notes') }}">
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
      <a href="{{ route('tenant.inventory.regradings.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
      <button type="submit" class="btn btn-primary px-4 fw-bold">
        <i class="bi bi-check2-circle me-1"></i> {{ __('Save Regrading Session') }}
      </button>
    </div>
  </form>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sourceProduct = document.getElementById('sourceProduct');
      const targetProduct = document.getElementById('targetProduct');
      const sourceQty = document.getElementById('sourceQty');
      const targetQty = document.getElementById('targetQty');
      const sourceCost = document.getElementById('sourceCost');
      const targetCost = document.getElementById('targetCost');
      const lossQtyDisplay = document.getElementById('lossQtyDisplay');
      const lossCostDisplay = document.getElementById('lossCostDisplay');

      sourceProduct.addEventListener('change', function () {
        const opt = sourceProduct.options[sourceProduct.selectedIndex];
        if (opt && opt.dataset.cost) {
          sourceCost.value = parseFloat(opt.dataset.cost).toFixed(2);
          calc();
        }
      });

      targetProduct.addEventListener('change', function () {
        const opt = targetProduct.options[targetProduct.selectedIndex];
        if (opt && opt.dataset.cost) {
          targetCost.value = parseFloat(opt.dataset.cost).toFixed(2);
          calc();
        }
      });

      [sourceQty, targetQty, sourceCost, targetCost].forEach(el => {
        el.addEventListener('input', calc);
      });

      function calc() {
        const sQ = parseFloat(sourceQty.value) || 0;
        const tQ = parseFloat(targetQty.value) || 0;
        const sC = parseFloat(sourceCost.value) || 0;

        const lossQ = Math.max(0, sQ - tQ);
        const lossC = lossQ * sC;

        lossQtyDisplay.textContent = lossQ.toFixed(2) + ' kg';
        lossCostDisplay.textContent = 'Rs. ' + lossC.toFixed(2);
      }
    });
  </script>
  @endpush
@endsection

