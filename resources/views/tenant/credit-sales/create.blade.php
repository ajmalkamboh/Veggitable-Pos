@extends('layouts.admin')

@section('title', __('Credit Customer Sale Entry (ادھار فروخت)'))

@section('content')
  <x-breadcrumb 
    :title="__('Credit Customer Sale Entry (ادھار گاہک فروخت)')" 
    :items="[
      __('In-Shop Operations') => '#',
      __('Credit Sales') => route('tenant.credit-sales.index'),
      __('New Credit Sale') => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.credit-sales.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> {{ __('Back to Credit Sales') }}
      </a>
      <a href="{{ route('tenant.pos.counter') }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-cart-check me-1"></i> {{ __('POS Cashier Counter') }}
      </a>
    </x-slot>
  </x-breadcrumb>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Please resolve the following errors:') }}</div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('tenant.credit-sales.store') }}" id="creditSaleForm">
    @csrf

    <div class="row g-4">
      <!-- Invoice Header & Line Items -->
      <div class="col-12 col-xl-8">
        <!-- Customer & Header Info Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-person-badge text-primary me-2"></i>{{ __('Credit Customer & Bill Information') }}
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
              {{ __('Khata / Credit Account Entry') }}
            </span>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <!-- Customer Selection -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">
                  {{ __('Select Credit Customer') }} <span class="text-danger">*</span>
                </label>
                <select name="customer_id" id="customerSelect" class="form-select @error('customer_id') is-invalid @enderror" required>
                  <option value="">{{ __('Choose Customer / ادھار گاہک منتخب کریں...') }}</option>
                  @foreach($customers as $c)
                    <option 
                      value="{{ $c->id }}" 
                      data-balance="{{ (float) $c->current_balance }}"
                      data-limit="{{ (float) $c->credit_limit }}"
                      data-phone="{{ $c->phone ?? $c->mobile ?? '' }}"
                      {{ old('customer_id') == $c->id ? 'selected' : '' }}
                    >
                      {{ $c->name }} @if($c->name_ur) ({{ $c->name_ur }}) @endif - {{ $c->phone ?? $c->code }} [{{ __('Khata Bal: ') }}{{ $tenant->currency }} {{ number_format($c->current_balance, 2) }}]
                    </option>
                  @endforeach
                </select>
                @error('customer_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <!-- Customer Live Khata Alert Box -->
                <div id="customerKhataInfo" class="mt-2 p-2 bg-light rounded-2 border d-none small">
                  <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Current Ledger Balance:') }}</span>
                    <strong id="custCurrentBalance" class="text-danger">{{ $tenant->currency }} 0.00</strong>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">{{ __('Credit Limit:') }}</span>
                    <span id="custCreditLimit" class="text-secondary">{{ __('No Limit') }}</span>
                  </div>
                </div>
              </div>

              <!-- Branch -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Dispatching Branch / Shop') }} <span class="text-danger">*</span></label>
                <select name="branch_id" id="branchSelect" class="form-select @error('branch_id') is-invalid @enderror" required>
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                      {{ $b->name }} @if($b->is_headquarters) (HQ) @endif
                    </option>
                  @endforeach
                </select>
                @error('branch_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Date -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Sale / Delivery Date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', now()->toDateString()) }}" required>
                @error('date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Internal Invoice # -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Generated Bill #') }}</label>
                <input type="text" class="form-control font-monospace bg-light" value="{{ $nextInvoiceNo }}" disabled readonly>
              </div>

              <!-- Payment Type Indicator -->
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">{{ __('Billing Method') }}</label>
                <div class="form-control bg-light text-primary fw-semibold d-flex align-items-center gap-2">
                  <i class="bi bi-journal-bookmark-fill"></i>
                  <span>{{ __('Credit Account (ادھار کھاتہ)') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Produce Item Lines Table (Similar to Standard Purchase Entry) -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-basket text-success me-2"></i>{{ __('Produce Items & Quantities') }}
              </h6>
              <small class="text-muted">{{ __('Item name with search, quantity, rate, and automatic line amount calculation.') }}</small>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="addItemRowBtn">
              <i class="bi bi-plus-lg me-1"></i> {{ __('Add Produce Item') }}
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="itemsTable">
              <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                  <th style="min-width: 240px;">{{ __('Vegetable / Item Name') }} <span class="text-danger">*</span></th>
                  <th style="width: 110px;">{{ __('Unit') }}</th>
                  <th style="width: 110px;">{{ __('Stock Avail') }}</th>
                  <th style="width: 120px;">{{ __('Quantity') }} <span class="text-danger">*</span></th>
                  <th style="width: 130px;">{{ __('Rate / Price') }} <span class="text-danger">*</span></th>
                  <th style="width: 140px;" class="text-end">{{ __('Amount') }}</th>
                  <th style="width: 45px;" class="text-center"></th>
                </tr>
              </thead>
              <tbody id="itemsTableBody">
                <!-- Initial Row 0 -->
                <tr class="item-row" data-index="0">
                  <td>
                    <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                      <option value="">{{ __('Select Produce Item...') }}</option>
                      @foreach($products as $p)
                        <option 
                          value="{{ $p->id }}" 
                          data-price="{{ (float) $p->selling_price }}"
                          data-cost="{{ (float) $p->cost_price }}"
                          data-unit-name="{{ $p->stockUnit?->name ?? 'Kilogram' }}"
                          data-unit-code="{{ $p->stockUnit?->code ?? 'kg' }}"
                          data-unit-id="{{ $p->stock_unit_id }}"
                          data-stock="{{ (float) ($stocks[$p->id] ?? 0) }}"
                        >
                          {{ $p->name }} @if($p->name_ur) ({{ $p->name_ur }}) @endif
                        </option>
                      @endforeach
                    </select>
                    <input type="hidden" name="items[0][unit_id]" class="unit-id-input" value="">
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1 unit-badge">-</span>
                  </td>
                  <td>
                    <span class="small fw-semibold stock-badge text-muted">-</span>
                  </td>
                  <td>
                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm text-end qty-input" step="0.001" min="0.001" placeholder="0.00" required>
                  </td>
                  <td>
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light text-muted">{{ $tenant->currency }}</span>
                      <input type="number" name="items[0][unit_price]" class="form-control text-end rate-input" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                  </td>
                  <td class="text-end fw-bold line-total font-monospace">
                    {{ $tenant->currency }} 0.00
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm border-0 py-0 px-2 remove-row-btn" title="Remove Item">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="card-footer bg-light py-2 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" id="addItemRowLink">
              <i class="bi bi-plus-circle me-1"></i> {{ __('Add another item line') }}
            </button>
            <span class="text-muted small">
              {{ __('Total Line Items: ') }} <strong id="totalItemCount">1</strong>
            </span>
          </div>
        </div>

        <!-- Notes / Instructions -->
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <label class="form-label fw-semibold small text-muted text-uppercase mb-1">
              <i class="bi bi-chat-left-text me-1"></i>{{ __('Order Notes / Delivery Narration') }}
            </label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Hotel delivery morning shift, crates returnable on next visit...">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>

      <!-- Financial Calculation & Khata Impact Sidebar -->
      <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 85px;">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-bold text-dark">
              <i class="bi bi-calculator text-primary me-2"></i>{{ __('Financial Summary & Khata Impact') }}
            </h6>
          </div>
          <div class="card-body">
            <!-- Subtotal -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">{{ __('Items Subtotal:') }}</span>
              <span class="fw-bold fs-6 font-monospace" id="subtotalDisplay">{{ $tenant->currency }} 0.00</span>
            </div>

            <!-- Delivery / Carriage Charges -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted d-flex justify-content-between">
                <span>{{ __('Delivery / Mazdoori / Carriage:') }}</span>
                <span class="text-success">+ {{ __('Add to Bill') }}</span>
              </label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                <input type="number" name="delivery_charges" id="deliveryChargesInput" class="form-control text-end" step="0.01" min="0" value="{{ old('delivery_charges', '0.00') }}">
              </div>
            </div>

            <!-- Discount -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted d-flex justify-content-between">
                <span>{{ __('Special Customer Discount:') }}</span>
                <span class="text-danger">- {{ __('Deduct') }}</span>
              </label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                <input type="number" name="discount_amount" id="discountInput" class="form-control text-end" step="0.01" min="0" value="{{ old('discount_amount', '0.00') }}">
              </div>
            </div>

            <hr class="my-3">

            <!-- Net Total Amount -->
            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-primary-subtle rounded-3 border border-primary-subtle">
              <span class="fw-bold text-primary">{{ __('Total Bill Amount:') }}</span>
              <span class="fw-bold fs-5 text-primary font-monospace" id="grandTotalDisplay">{{ $tenant->currency }} 0.00</span>
            </div>

            <!-- Paid On Spot (Partial Payment) -->
            <div class="mb-3">
              <label class="form-label small fw-semibold text-dark">
                {{ __('Cash Paid on Spot (Optional Partial Payment):') }}
              </label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-success fw-bold">{{ $tenant->currency }}</span>
                <input type="number" name="paid_amount" id="paidAmountInput" class="form-control text-end fw-bold text-success" step="0.01" min="0" value="{{ old('paid_amount', '0.00') }}" placeholder="0.00">
              </div>
              <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                {{ __('Leave 0 if 100% credit/unpaid. Any paid amount will be deposited into selected account.') }}
              </small>
            </div>

            <!-- Financial Account (If Paid > 0) -->
            <div class="mb-3" id="financialAccountGroup" style="display: none;">
              <label class="form-label small fw-semibold text-dark">{{ __('Deposit Into Cash/Bank Account:') }} <span class="text-danger">*</span></label>
              <select name="financial_account_id" id="accountSelect" class="form-select form-select-sm">
                <option value="">{{ __('Select Receiving Account...') }}</option>
                @foreach($accounts as $acc)
                  <option value="{{ $acc->id }}" {{ old('financial_account_id') == $acc->id ? 'selected' : '' }}>
                    {{ $acc->name }} ({{ ucfirst($acc->account_type) }})
                  </option>
                @endforeach
              </select>
            </div>

            <hr class="my-3">

            <!-- Khata Impact Calculation Box -->
            <div class="p-3 bg-light rounded-3 border mb-4">
              <h6 class="small text-uppercase fw-bold text-muted mb-2">
                <i class="bi bi-journal-text me-1"></i>{{ __('Customer Khata Impact / ادھار اثر') }}
              </h6>
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>{{ __('Net Added to Khata (Due):') }}</span>
                <strong id="dueAmountDisplay" class="text-dark">{{ $tenant->currency }} 0.00</strong>
              </div>
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>{{ __('Previous Ledger Balance:') }}</span>
                <span id="prevBalanceDisplay" class="text-secondary">{{ $tenant->currency }} 0.00</span>
              </div>
              <div class="d-flex justify-content-between small pt-2 border-top mt-1">
                <span class="fw-bold text-danger">{{ __('New Projected Balance:') }}</span>
                <strong id="projectedBalanceDisplay" class="text-danger font-monospace fs-6">{{ $tenant->currency }} 0.00</strong>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm" id="submitBtn">
                <i class="bi bi-check2-circle fs-5"></i>
                <span>{{ __('Record Credit Sale & Generate Bill') }}</span>
              </button>
              <a href="{{ route('tenant.credit-sales.index') }}" class="btn btn-outline-secondary btn-sm">
                {{ __('Cancel') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- JavaScript for Dynamic Rows, Calculations, and Khata Preview -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const currency = "{{ $tenant->currency }}";
      let rowIndex = 1;

      const customerSelect = document.getElementById('customerSelect');
      const customerKhataInfo = document.getElementById('customerKhataInfo');
      const custCurrentBalance = document.getElementById('custCurrentBalance');
      const custCreditLimit = document.getElementById('custCreditLimit');

      const itemsTableBody = document.getElementById('itemsTableBody');
      const addItemRowBtn = document.getElementById('addItemRowBtn');
      const addItemRowLink = document.getElementById('addItemRowLink');
      const totalItemCount = document.getElementById('totalItemCount');

      const subtotalDisplay = document.getElementById('subtotalDisplay');
      const deliveryChargesInput = document.getElementById('deliveryChargesInput');
      const discountInput = document.getElementById('discountInput');
      const grandTotalDisplay = document.getElementById('grandTotalDisplay');
      const paidAmountInput = document.getElementById('paidAmountInput');
      const financialAccountGroup = document.getElementById('financialAccountGroup');
      const accountSelect = document.getElementById('accountSelect');

      const dueAmountDisplay = document.getElementById('dueAmountDisplay');
      const prevBalanceDisplay = document.getElementById('prevBalanceDisplay');
      const projectedBalanceDisplay = document.getElementById('projectedBalanceDisplay');

      // 1. Customer Selection & Khata Info
      function updateCustomerInfo() {
        const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
          const bal = parseFloat(selectedOpt.dataset.balance || 0);
          const limit = parseFloat(selectedOpt.dataset.limit || 0);

          customerKhataInfo.classList.remove('d-none');
          custCurrentBalance.textContent = currency + ' ' + bal.toLocaleString('en-US', { minimumFractionDigits: 2 });
          prevBalanceDisplay.textContent = currency + ' ' + bal.toLocaleString('en-US', { minimumFractionDigits: 2 });

          if (limit > 0) {
            custCreditLimit.textContent = currency + ' ' + limit.toLocaleString('en-US', { minimumFractionDigits: 2 });
          } else {
            custCreditLimit.textContent = "{{ __('No Limit') }}";
          }
        } else {
          customerKhataInfo.classList.add('d-none');
          prevBalanceDisplay.textContent = currency + ' 0.00';
        }
        recalculateTotals();
      }

      customerSelect.addEventListener('change', updateCustomerInfo);
      updateCustomerInfo();

      // 2. Add New Item Row
      function addRow() {
        const templateRow = document.querySelector('.item-row');
        const newRow = templateRow.cloneNode(true);
        newRow.dataset.index = rowIndex;

        // Reset inputs and update names
        const prodSelect = newRow.querySelector('.product-select');
        prodSelect.name = `items[${rowIndex}][product_id]`;
        prodSelect.value = '';

        const unitInput = newRow.querySelector('.unit-id-input');
        unitInput.name = `items[${rowIndex}][unit_id]`;
        unitInput.value = '';

        const unitBadge = newRow.querySelector('.unit-badge');
        unitBadge.textContent = '-';

        const stockBadge = newRow.querySelector('.stock-badge');
        stockBadge.textContent = '-';
        stockBadge.className = 'small fw-semibold stock-badge text-muted';

        const qtyInput = newRow.querySelector('.qty-input');
        qtyInput.name = `items[${rowIndex}][quantity]`;
        qtyInput.value = '';

        const rateInput = newRow.querySelector('.rate-input');
        rateInput.name = `items[${rowIndex}][unit_price]`;
        rateInput.value = '';

        const lineTotal = newRow.querySelector('.line-total');
        lineTotal.textContent = currency + ' 0.00';

        attachRowEvents(newRow);
        itemsTableBody.appendChild(newRow);
        rowIndex++;
        updateRowCounter();
      }

      addItemRowBtn.addEventListener('click', addRow);
      addItemRowLink.addEventListener('click', addRow);

      function updateRowCounter() {
        totalItemCount.textContent = itemsTableBody.querySelectorAll('.item-row').length;
      }

      // 3. Attach Events to Row Inputs
      function attachRowEvents(row) {
        const prodSelect = row.querySelector('.product-select');
        const unitInput = row.querySelector('.unit-id-input');
        const unitBadge = row.querySelector('.unit-badge');
        const stockBadge = row.querySelector('.stock-badge');
        const qtyInput = row.querySelector('.qty-input');
        const rateInput = row.querySelector('.rate-input');
        const lineTotal = row.querySelector('.line-total');
        const removeBtn = row.querySelector('.remove-row-btn');

        prodSelect.addEventListener('change', function () {
          const opt = prodSelect.options[prodSelect.selectedIndex];
          if (opt && opt.value) {
            const price = parseFloat(opt.dataset.price || 0);
            const unitName = opt.dataset.unitName || 'kg';
            const unitCode = opt.dataset.unitCode || 'kg';
            const unitId = opt.dataset.unitId || '';
            const stock = parseFloat(opt.dataset.stock || 0);

            rateInput.value = price > 0 ? price.toFixed(2) : '';
            unitInput.value = unitId;
            unitBadge.textContent = unitCode;

            stockBadge.textContent = stock.toFixed(1) + ' ' + unitCode;
            if (stock <= 0) {
              stockBadge.className = 'small fw-semibold stock-badge text-danger';
            } else if (stock <= 10) {
              stockBadge.className = 'small fw-semibold stock-badge text-warning';
            } else {
              stockBadge.className = 'small fw-semibold stock-badge text-success';
            }
          } else {
            rateInput.value = '';
            unitInput.value = '';
            unitBadge.textContent = '-';
            stockBadge.textContent = '-';
            stockBadge.className = 'small fw-semibold stock-badge text-muted';
          }
          calculateLine(row);
        });

        qtyInput.addEventListener('input', () => calculateLine(row));
        rateInput.addEventListener('input', () => calculateLine(row));

        removeBtn.addEventListener('click', function () {
          if (itemsTableBody.querySelectorAll('.item-row').length > 1) {
            row.remove();
            updateRowCounter();
            recalculateTotals();
          } else {
            alert("{{ __('At least one item row is required.') }}");
          }
        });
      }

      function calculateLine(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
        const amount = Math.round((qty * rate) * 100) / 100;
        row.querySelector('.line-total').textContent = currency + ' ' + amount.toLocaleString('en-US', { minimumFractionDigits: 2 });
        recalculateTotals();
      }

      // Attach to initial row
      document.querySelectorAll('.item-row').forEach(attachRowEvents);

      // 4. Financial Calculations & Khata Impact
      function recalculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(function (row) {
          const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
          const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
          subtotal += Math.round((qty * rate) * 100) / 100;
        });

        const delivery = parseFloat(deliveryChargesInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const grandTotal = Math.max(0, subtotal + delivery - discount);

        const paid = Math.min(grandTotal, parseFloat(paidAmountInput.value) || 0);
        const due = Math.max(0, grandTotal - paid);

        // Toggle Account select if paid amount > 0
        if (paid > 0) {
          financialAccountGroup.style.display = 'block';
          accountSelect.required = true;
        } else {
          financialAccountGroup.style.display = 'none';
          accountSelect.required = false;
        }

        // Selected customer balance
        const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
        const prevBal = (selectedOpt && selectedOpt.value) ? parseFloat(selectedOpt.dataset.balance || 0) : 0;
        const newProjectedBal = prevBal + due;

        // Display
        subtotalDisplay.textContent = currency + ' ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
        grandTotalDisplay.textContent = currency + ' ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
        dueAmountDisplay.textContent = currency + ' ' + due.toLocaleString('en-US', { minimumFractionDigits: 2 });
        projectedBalanceDisplay.textContent = currency + ' ' + newProjectedBal.toLocaleString('en-US', { minimumFractionDigits: 2 });
      }

      deliveryChargesInput.addEventListener('input', recalculateTotals);
      discountInput.addEventListener('input', recalculateTotals);
      paidAmountInput.addEventListener('input', recalculateTotals);
    });
  </script>
@endsection
