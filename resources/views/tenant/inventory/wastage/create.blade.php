@extends('layouts.admin')

@section('title', __('Record Spoilage, Damage & Wastage'))

@section('content')
  <x-breadcrumb 
    :title="__('Record Spoilage / Wastage')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Wastage') => route('tenant.inventory.wastage.index'),
      __('Record Spoilage') => route('tenant.inventory.wastage.create')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.wastage.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
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

  <form method="POST" action="{{ route('tenant.inventory.wastage.store') }}" id="wastageForm">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-journal-x me-2 text-danger"></i>{{ __('Wastage Voucher Header') }}
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold required">{{ __('Branch Outlet') }}</label>
            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
              @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id', $activeBranchId) == $b->id ? 'selected' : '' }}>
                  {{ $b->name }}
                </option>
              @endforeach
            </select>
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold required">{{ __('Wastage Date') }}</label>
            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
            @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold required">{{ __('Primary Spoilage Reason') }}</label>
            <select name="reason" class="form-select @error('reason') is-invalid @enderror" required>
              <option value="spoilage" {{ old('reason') === 'spoilage' ? 'selected' : '' }}>{{ __('Rotten / Spoilage (خراب / گلا سڑا)') }}</option>
              <option value="drying_shrinkage" {{ old('reason') === 'drying_shrinkage' ? 'selected' : '' }}>{{ __('Moisture / Drying Shrinkage (سوکھنا / وزن کی کمی)') }}</option>
              <option value="handling_damage" {{ old('reason') === 'handling_damage' ? 'selected' : '' }}>{{ __('Handling / Transit Damage (ٹوٹ پھوٹ / نقصان)') }}</option>
              <option value="pest_damage" {{ old('reason') === 'pest_damage' ? 'selected' : '' }}>{{ __('Rodent / Pest Damage (کیڑا / چوہا)') }}</option>
              <option value="trimming_cleaning" {{ old('reason') === 'trimming_cleaning' ? 'selected' : '' }}>{{ __('Trimming / Cleaning Waste (صفائی / چھانٹی)') }}</option>
              <option value="expired" {{ old('reason') === 'expired' ? 'selected' : '' }}>{{ __('Expired Shelf Life (مدت ختم)') }}</option>
              <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>{{ __('Other Spoilage') }}</option>
            </select>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">{{ __('General Inspection Notes') }}</label>
            <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('e.g., Morning sorting culled rotten tomatoes from Mandi lot...') }}">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Items Section -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-basket2 me-2 text-danger"></i>{{ __('Damaged / Wasted Produce Items') }}
        </h6>
        <button type="button" class="btn btn-outline-danger btn-sm" id="addItemBtn">
          <i class="bi bi-plus-circle me-1"></i> {{ __('Add Produce Item') }}
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="itemsTable">
          <thead class="table-light">
            <tr>
              <th style="width: 28%;">{{ __('Product') }}</th>
              <th style="width: 14%;">{{ __('Quantity (kg)') }}</th>
              <th style="width: 14%;">{{ __('Cost Price (Rs)') }}</th>
              <th style="width: 14%;">{{ __('Loss Value (Rs)') }}</th>
              <th style="width: 16%;">{{ __('Disposal Method') }}</th>
              <th style="width: 10%;">{{ __('Notes') }}</th>
              <th style="width: 4%;" class="text-center">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            <!-- Dynamic item rows injected by JS -->
          </tbody>
          <tfoot class="table-light fw-bold">
            <tr>
              <td>{{ __('Total Summary') }}:</td>
              <td id="footerTotalQty">0.00 kg</td>
              <td>-</td>
              <td class="text-danger" id="footerTotalLoss">Rs. 0.00</td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Submission & Authorization Box -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <label class="form-label fw-semibold d-block mb-1">{{ __('Approval Workflow') }}</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status" id="statusApproved" value="approved" checked>
            <label class="form-check-label fw-semibold text-success" for="statusApproved">
              <i class="bi bi-check-circle me-1"></i> {{ __('Approve Immediately & Deduct Stock') }}
            </label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status" id="statusPending" value="pending_approval">
            <label class="form-check-label fw-semibold text-warning" for="statusPending">
              <i class="bi bi-hourglass me-1"></i> {{ __('Submit as Draft / Pending Approval') }}
            </label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="{{ route('tenant.inventory.wastage.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
          <button type="submit" class="btn btn-danger px-4 fw-bold">
            <i class="bi bi-check2-circle me-1"></i> {{ __('Save Wastage Voucher') }}
          </button>
        </div>
      </div>
    </div>
  </form>

  <template id="rowTemplate">
    <tr class="item-row">
      <td>
        <select name="items[INDEX][product_id]" class="form-select form-select-sm product-select" required>
          <option value="">{{ __('Select Product...') }}</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $stocks[$p->id] ?? 0 }}" data-unit="{{ $p->unit?->code ?? 'kg' }}">
              {{ $p->name }} ({{ $p->code }}) - [Stock: {{ number_format($stocks[$p->id] ?? 0, 2) }} {{ $p->unit?->code ?? 'kg' }}]
            </option>
          @endforeach
        </select>
      </td>
      <td>
        <input type="number" step="0.001" min="0.001" name="items[INDEX][quantity]" class="form-control form-control-sm qty-input" placeholder="0.000" required>
      </td>
      <td>
        <input type="number" step="0.01" min="0" name="items[INDEX][unit_cost]" class="form-control form-control-sm cost-input" placeholder="0.00" required>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm line-loss-display text-danger fw-bold bg-light" readonly value="Rs. 0.00">
      </td>
      <td>
        <select name="items[INDEX][disposal_method]" class="form-select form-select-sm">
          <option value="discarded">{{ __('Discarded / Dumped (پھینک دیا)') }}</option>
          <option value="composted">{{ __('Organic Fertilizer / Compost (کھاد)') }}</option>
          <option value="donated">{{ __('Donated / Animal Feed (جانوروں کا چارہ)') }}</option>
          <option value="salvaged">{{ __('Juiced / Salvaged (استعمال میں لایا)') }}</option>
        </select>
      </td>
      <td>
        <input type="text" name="items[INDEX][notes]" class="form-control form-control-sm" placeholder="{{ __('Optional note') }}">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  </template>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let rowIndex = 0;
      const itemsBody = document.getElementById('itemsBody');
      const rowTemplate = document.getElementById('rowTemplate').innerHTML;
      const addItemBtn = document.getElementById('addItemBtn');

      function addRow() {
        const html = rowTemplate.replace(/INDEX/g, rowIndex++);
        itemsBody.insertAdjacentHTML('beforeend', html);
        attachRowEvents(itemsBody.lastElementChild);
        calculateTotals();
      }

      function attachRowEvents(row) {
        const productSelect = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.qty-input');
        const costInput = row.querySelector('.cost-input');
        const lineLossDisplay = row.querySelector('.line-loss-display');
        const removeBtn = row.querySelector('.remove-row-btn');

        productSelect.addEventListener('change', function () {
          const selectedOption = productSelect.options[productSelect.selectedIndex];
          if (selectedOption && selectedOption.value) {
            costInput.value = parseFloat(selectedOption.dataset.cost || 0).toFixed(2);
            updateLine();
          }
        });

        qtyInput.addEventListener('input', updateLine);
        costInput.addEventListener('input', updateLine);

        function updateLine() {
          const qty = parseFloat(qtyInput.value) || 0;
          const cost = parseFloat(costInput.value) || 0;
          const total = qty * cost;
          lineLossDisplay.value = 'Rs. ' + total.toFixed(2);
          calculateTotals();
        }

        removeBtn.addEventListener('click', function () {
          if (itemsBody.querySelectorAll('.item-row').length > 1) {
            row.remove();
            calculateTotals();
          } else {
            alert('{{ __("At least one produce item is required.") }}');
          }
        });
      }

      function calculateTotals() {
        let totalQty = 0;
        let totalLoss = 0;

        document.querySelectorAll('.item-row').forEach(row => {
          const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
          const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
          totalQty += qty;
          totalLoss += (qty * cost);
        });

        document.getElementById('footerTotalQty').textContent = totalQty.toFixed(2) + ' kg';
        document.getElementById('footerTotalLoss').textContent = 'Rs. ' + totalLoss.toFixed(2);
      }

      addItemBtn.addEventListener('click', addRow);

      // Add initial row
      addRow();
    });
  </script>
  @endpush
@endsection

