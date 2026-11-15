@extends('layouts.admin')

@section('title', __('New Mandi Purchase'))

@section('content')
  <x-breadcrumb 
    :title="__('Record Mandi Purchase')" 
    :items="[
      __('Procurement') => '#',
      __('Purchases') => route('tenant.purchases.index'),
      __('New Purchase') => '#'
    ]"
  />

  <form method="POST" action="{{ route('tenant.purchases.store') }}" enctype="multipart/form-data" id="purchaseForm">
    @csrf

    <div class="row g-4">
      <!-- Invoice Header & Item Lines -->
      <div class="col-12 col-xl-8">
        <!-- Header Info Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="card-title mb-0 fw-bold">{{ __('Invoice & Supplier Information') }}</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <!-- Supplier Select -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Supplier / Aarthi') }} <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplierSelect" class="form-select @error('supplier_id') is-invalid @enderror" required>
                  <option value="">{{ __('Select Mandi Supplier / Aarthi...') }}</option>
                  @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" data-commission="{{ (float) $supplier->commission_rate }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                      {{ $supplier->name }} @if($supplier->mandi_stall_no) ({{ $supplier->mandi_stall_no }}) @endif
                    </option>
                  @endforeach
                </select>
                @error('supplier_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Receiving Branch -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Receiving Branch / Warehouse') }} <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                  @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                      {{ $branch->name }} @if($branch->is_headquarters) (HQ) @endif
                    </option>
                  @endforeach
                </select>
                @error('branch_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Date -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Purchase Date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', now()->toDateString()) }}" required>
                @error('date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Mandi Parchi # -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Mandi Auction Parchi / Slip #') }}</label>
                <input type="text" name="supplier_invoice_no" class="form-control font-monospace" value="{{ old('supplier_invoice_no') }}" placeholder="e.g. PARCHI-7892">
              </div>

              <!-- Generated Invoice # -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Internal Invoice #') }}</label>
                <input type="text" class="form-control font-monospace bg-light" value="{{ $nextInvoiceNo }}" disabled readonly>
              </div>
            </div>
          </div>
        </div>

        <!-- Purchase Lot Items Table Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0 fw-bold">{{ __('Produce Lots & Weighing Scale Entry') }}</h6>
              <small class="text-muted">{{ __('Tare weight will deduct empty crates/sacks to calculate net vegetable weight.') }}</small>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addItemRowBtn">
              <i class="bi bi-plus-lg me-1"></i> {{ __('Add Lot Item') }}
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="itemsTable">
              <thead class="table-light">
                <tr class="small">
                  <th style="min-width: 170px;">{{ __('Product') }} <span class="text-danger">*</span></th>
                  <th style="width: 110px;">{{ __('Unit') }}</th>
                  <th style="width: 90px;">{{ __('Quantity') }} <span class="text-danger">*</span></th>
                  <th style="width: 100px;">{{ __('Gross Wt (kg)') }}</th>
                  <th style="width: 100px;">{{ __('Tare Wt (kg)') }}</th>
                  <th style="width: 100px;">{{ __('Net Wt (kg)') }}</th>
                  <th style="width: 110px;">{{ __('Rate / Price') }} <span class="text-danger">*</span></th>
                  <th style="width: 120px;" class="text-end">{{ __('Total') }}</th>
                  <th style="width: 40px;"></th>
                </tr>
              </thead>
              <tbody id="itemsTableBody">
                <!-- Row 0 -->
                <tr class="item-row">
                  <td>
                    <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                      <option value="">{{ __('Select Product...') }}</option>
                      @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ (float) $product->cost_price }}" data-unit="{{ $product->purchase_unit_id ?? $product->stock_unit_id }}">
                          {{ $product->name }} @if($product->name_ur) ({{ $product->name_ur }}) @endif
                        </option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select name="items[0][unit_id]" class="form-select form-select-sm unit-select">
                      <option value="">{{ __('Default') }}</option>
                      @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="number" step="0.001" min="0.001" name="items[0][quantity]" class="form-control form-select-sm qty-input font-monospace" value="1" required>
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" name="items[0][gross_weight]" class="form-control form-select-sm gross-input font-monospace" placeholder="0.00">
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" name="items[0][tare_weight]" class="form-control form-select-sm tare-input font-monospace" placeholder="0.00">
                  </td>
                  <td>
                    <input type="number" step="0.01" class="form-control form-select-sm net-input font-monospace bg-light" placeholder="0.00" readonly>
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" name="items[0][unit_price]" class="form-control form-select-sm price-input font-monospace" placeholder="0.00" required>
                  </td>
                  <td class="text-end">
                    <span class="fw-bold font-monospace line-total text-dark">0.00</span>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-row-btn" style="display: none;">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Financial Summary & Immediate Payment -->
      <div class="col-12 col-xl-4">
        <!-- Charges Breakdown Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="card-title mb-0 fw-bold">{{ __('Financial Breakdown & Mandi Fees') }}</h6>
          </div>
          <div class="card-body">
            <!-- Subtotal -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">{{ __('Produce Subtotal') }}:</span>
              <span class="fw-bold font-monospace" id="subtotalDisplay">0.00</span>
            </div>

            <!-- Commission -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted">{{ __('Mandi Commission') }}:</span>
                <span class="font-monospace fw-semibold text-danger" id="commissionDisplay">0.00</span>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">{{ __('Rate %') }}</span>
                <input type="number" step="0.01" min="0" max="100" name="commission_rate" id="commissionRateInput" class="form-control font-monospace" value="{{ old('commission_rate', '5.00') }}">
                <span class="input-group-text bg-light">%</span>
              </div>
            </div>

            <!-- Mandi Fee (Chungi) -->
            <div class="mb-2">
              <label class="form-label small text-muted mb-1">{{ __('Market Committee / Chungi Tax') }}</label>
              <input type="number" step="0.01" min="0" name="mandi_fee" id="mandiFeeInput" class="form-control form-control-sm font-monospace fee-input" value="{{ old('mandi_fee', '0.00') }}">
            </div>

            <!-- Freight / Mazdoori -->
            <div class="mb-2">
              <label class="form-label small text-muted mb-1">{{ __('Freight / Carriage / Mazdoori') }}</label>
              <input type="number" step="0.01" min="0" name="freight_charges" id="freightInput" class="form-control form-control-sm font-monospace fee-input" value="{{ old('freight_charges', '0.00') }}">
            </div>

            <!-- Other Charges -->
            <div class="mb-2">
              <label class="form-label small text-muted mb-1">{{ __('Other Overhead Charges') }}</label>
              <input type="number" step="0.01" min="0" name="other_charges" id="otherInput" class="form-control form-control-sm font-monospace fee-input" value="{{ old('other_charges', '0.00') }}">
            </div>

            <!-- Discount -->
            <div class="mb-3">
              <label class="form-label small text-muted mb-1">{{ __('Supplier Discount') }}</label>
              <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control form-control-sm font-monospace fee-input" value="{{ old('discount', '0.00') }}">
            </div>

            <!-- Grand Total -->
            <div class="p-3 bg-light rounded text-center border mt-3">
              <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Net Payable Bill') }}</span>
              <h3 class="mb-0 fw-bold font-monospace text-primary mt-1">
                {{ $tenant->currency }} <span id="grandTotalDisplay">0.00</span>
              </h3>
            </div>
          </div>
        </div>

        <!-- Immediate Payment Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3">
            <h6 class="card-title mb-0 fw-bold">{{ __('Payment at Mandi Auction') }}</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Paid Amount (Cash / Advance)') }}</label>
              <div class="input-group">
                <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" class="form-control font-monospace" value="{{ old('paid_amount', '0.00') }}">
              </div>
              <small class="text-muted">{{ __('Leave 0.00 if purchasing entirely on credit.') }}</small>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Payment Account') }}</label>
              <select name="financial_account_id" class="form-select form-select-sm">
                <option value="">{{ __('Select account (if paying now)...') }}</option>
                @foreach($accounts as $acc)
                  <option value="{{ $acc->id }}" {{ old('financial_account_id') == $acc->id ? 'selected' : '' }}>
                    {{ $acc->name }} ({{ ucfirst($acc->type) }})
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Mandi Auction Slip Photo') }}</label>
              <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*">
              <small class="text-muted">{{ __('Upload Mandi parchi or weighing scale ticket.') }}</small>
            </div>
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-lg">{{ __('Save & Post Purchase Invoice') }}</button>
          <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
      </div>
    </div>
  </form>

  <!-- JavaScript to dynamically compute rows, net tare weights, commissions and total bill -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let rowIdx = 1;
      const tbody = document.getElementById('itemsTableBody');
      const addRowBtn = document.getElementById('addItemRowBtn');
      const supplierSelect = document.getElementById('supplierSelect');
      const commRateInput = document.getElementById('commissionRateInput');

      // Update commission rate when supplier changes
      if (supplierSelect) {
        supplierSelect.addEventListener('change', function () {
          const selected = this.options[this.selectedIndex];
          if (selected && selected.dataset.commission) {
            commRateInput.value = parseFloat(selected.dataset.commission).toFixed(2);
            calculateGrandTotals();
          }
        });
      }

      // Add Row
      addRowBtn.addEventListener('click', function () {
        const firstRow = tbody.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);

        // Update input names with new rowIdx
        newRow.querySelectorAll('input, select').forEach(input => {
          input.name = input.name.replace(/\[\d+\]/, '[' + rowIdx + ']');
          if (input.classList.contains('qty-input')) input.value = '1';
          else if (input.classList.contains('price-input')) input.value = '';
          else if (input.classList.contains('gross-input') || input.classList.contains('tare-input') || input.classList.contains('net-input')) input.value = '';
          else if (input.tagName === 'SELECT') input.selectedIndex = 0;
        });

        newRow.querySelector('.line-total').innerText = '0.00';
        newRow.querySelector('.remove-row-btn').style.display = 'inline-block';

        tbody.appendChild(newRow);
        rowIdx++;
        bindRowEvents(newRow);
      });

      // Bind events to a row
      function bindRowEvents(row) {
        const productSelect = row.querySelector('.product-select');
        const priceInput = row.querySelector('.price-input');
        const qtyInput = row.querySelector('.qty-input');
        const grossInput = row.querySelector('.gross-input');
        const tareInput = row.querySelector('.tare-input');
        const netInput = row.querySelector('.net-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        if (productSelect) {
          productSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.price && parseFloat(opt.dataset.price) > 0) {
              priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
            }
            recalcLine(row);
          });
        }

        [qtyInput, grossInput, tareInput, priceInput].forEach(inp => {
          if (inp) inp.addEventListener('input', () => recalcLine(row));
        });

        if (removeBtn) {
          removeBtn.addEventListener('click', function () {
            if (tbody.querySelectorAll('.item-row').length > 1) {
              row.remove();
              calculateGrandTotals();
            }
          });
        }
      }

      function recalcLine(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const gross = parseFloat(row.querySelector('.gross-input').value) || 0;
        const tare = parseFloat(row.querySelector('.tare-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const netInput = row.querySelector('.net-input');
        const lineTotalSpan = row.querySelector('.line-total');

        let netWeight = 0;
        if (gross > 0) {
          netWeight = Math.max(0, gross - tare);
          netInput.value = netWeight.toFixed(2);
        } else {
          netInput.value = '';
        }

        const effectiveMultiplier = netWeight > 0 ? netWeight : qty;
        const lineTotal = effectiveMultiplier * price;
        lineTotalSpan.innerText = lineTotal.toFixed(2);

        calculateGrandTotals();
      }

      function calculateGrandTotals() {
        let subtotal = 0;
        tbody.querySelectorAll('.item-row').forEach(row => {
          subtotal += parseFloat(row.querySelector('.line-total').innerText) || 0;
        });

        document.getElementById('subtotalDisplay').innerText = subtotal.toFixed(2);

        const commRate = parseFloat(commRateInput.value) || 0;
        const commAmount = (subtotal * commRate) / 100;
        document.getElementById('commissionDisplay').innerText = commAmount.toFixed(2);

        const mandiFee = parseFloat(document.getElementById('mandiFeeInput').value) || 0;
        const freight = parseFloat(document.getElementById('freightInput').value) || 0;
        const other = parseFloat(document.getElementById('otherInput').value) || 0;
        const discount = parseFloat(document.getElementById('discountInput').value) || 0;

        const grandTotal = Math.max(0, subtotal + commAmount + mandiFee + freight + other - discount);
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toFixed(2);
      }

      // Initial bind
      tbody.querySelectorAll('.item-row').forEach(row => bindRowEvents(row));

      document.querySelectorAll('.fee-input, #commissionRateInput').forEach(inp => {
        inp.addEventListener('input', calculateGrandTotals);
      });
    });
  </script>
@endsection

