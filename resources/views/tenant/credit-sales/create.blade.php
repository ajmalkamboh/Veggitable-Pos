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

  <!-- Keyboard Shortcuts Bar -->
  <div class="card border-0 shadow-sm mb-3 bg-light-subtle">
    <div class="card-body py-2 px-3">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
        <div class="d-flex align-items-center gap-2 text-primary fw-semibold">
          <i class="bi bi-keyboard fs-5"></i>
          <span>{{ __('Keyboard-First Speed Entry / تیز رفتار کی بورڈ اندراج:') }}</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="badge bg-white text-dark border px-2 py-1"><kbd class="bg-dark text-white">F2</kbd> {{ __('Select Item') }}</span>
          <span class="badge bg-white text-dark border px-2 py-1"><kbd class="bg-dark text-white">Enter</kbd> {{ __('Next Field / Add to Top') }}</span>
          <span class="badge bg-white text-dark border px-2 py-1"><kbd class="bg-dark text-white">F4</kbd> {{ __('Customer') }}</span>
          <span class="badge bg-white text-dark border px-2 py-1"><kbd class="bg-dark text-white">F8</kbd> {{ __('Paid Amount') }}</span>
          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><kbd class="bg-success text-white">Ctrl + Enter</kbd> {{ __('Save & Record Bill') }}</span>
        </div>
      </div>
    </div>
  </div>

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
                <label class="form-label fw-semibold" for="customerSelect">
                  {{ __('Select Credit Customer') }} <span class="text-danger">*</span>
                  <small class="text-muted fw-normal">({{ __('Press F4') }})</small>
                </label>
                <select name="customer_id" id="customerSelect" class="form-select @error('customer_id') is-invalid @enderror" required autofocus>
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
                <label class="form-label fw-semibold" for="branchSelect">{{ __('Dispatching Branch / Shop') }} <span class="text-danger">*</span></label>
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

        <!-- Produce Item Lines Table Card with Top Fast-Entry Row -->
        <div class="card border-0 shadow-sm mb-4" id="itemsTableCard">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-basket text-success me-2"></i>{{ __('Produce Items & Quantities') }}
              </h6>
              <small class="text-muted">{{ __('New items appear at the top. Fast keyboard entry with Enter / F2.') }}</small>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
              {{ __('Total Items: ') }} <strong id="totalItemCount">0</strong>
            </span>
          </div>

          <!-- DEDICATED TOP FAST-ENTRY ROW (KEYBOARD-FRIENDLY) -->
          <div class="p-3 bg-light border-bottom" id="quickEntryRow">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-bold text-dark small text-uppercase d-flex align-items-center gap-1">
                <i class="bi bi-lightning-charge-fill text-warning"></i>
                <span>{{ __('Add New Item at Top / نیا آئٹم اوپر شامل کریں') }}</span>
              </span>
              <span class="text-muted small">
                {{ __('Select Item -> Type Qty -> Hit Enter -> Item goes to TOP') }}
              </span>
            </div>

            <div class="row g-2 align-items-end">
              <!-- Item Selector -->
              <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1" for="quickProductSelect">
                  {{ __('Vegetable / Item') }} <kbd class="bg-light text-dark border small ms-1">F2</kbd> <span class="text-danger">*</span>
                </label>
                <select id="quickProductSelect" class="form-select form-select-sm fw-semibold">
                  <option value="">{{ __('Select Produce Item... / سبزی منتخب کریں') }}</option>
                  @foreach($products as $p)
                    <option 
                      value="{{ $p->id }}" 
                      data-name="{{ $p->name }} @if($p->name_ur) ({{ $p->name_ur }}) @endif"
                      data-price="{{ (float) $p->selling_price }}"
                      data-cost="{{ (float) $p->cost_price }}"
                      data-unit-name="{{ $p->stockUnit?->name ?? 'Kilogram' }}"
                      data-unit-code="{{ $p->stockUnit?->code ?? 'kg' }}"
                      data-unit-id="{{ $p->stock_unit_id }}"
                      data-stock="{{ (float) ($stocks[$p->id] ?? 0) }}"
                    >
                      {{ $p->name }} @if($p->name_ur) ({{ $p->name_ur }}) @endif - [{{ $tenant->currency }} {{ number_format($p->selling_price, 2) }}/{{ $p->stockUnit?->code ?? 'kg' }}]
                    </option>
                  @endforeach
                </select>
              </div>

              <!-- Quantity Input with Unit indicator -->
              <div class="col-6 col-md-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-muted mb-0" for="quickQtyInput">
                    {{ __('Quantity') }} <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-secondary-subtle text-secondary px-1" id="quickUnitBadge" style="font-size: 0.7rem;">kg</span>
                </div>
                <input type="number" id="quickQtyInput" class="form-control form-control-sm text-end font-monospace fw-bold" step="0.001" min="0.001" placeholder="0.00">
              </div>

              <!-- Rate Input with Stock indicator -->
              <div class="col-6 col-md-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label small fw-semibold text-muted mb-0" for="quickRateInput">
                    {{ __('Rate / Price') }} <span class="text-danger">*</span>
                  </label>
                  <span class="small text-muted" id="quickStockBadge" style="font-size: 0.7rem;">{{ __('Stock: -') }}</span>
                </div>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white small px-1">{{ $tenant->currency }}</span>
                  <input type="number" id="quickRateInput" class="form-control form-control-sm text-end font-monospace fw-bold" step="0.01" min="0" placeholder="0.00">
                </div>
              </div>

              <!-- Line Amount Preview -->
              <div class="col-8 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">{{ __('Line Amount') }}</label>
                <div class="form-control form-control-sm bg-white text-end font-monospace fw-bold text-primary" id="quickLineTotal">
                  {{ $tenant->currency }} 0.00
                </div>
              </div>

              <!-- Add to Top Button -->
              <div class="col-4 col-md-1">
                <button type="button" id="quickAddBtn" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" title="{{ __('Add item to the top of the list (Enter)') }}">
                  <i class="bi bi-arrow-down-left-square me-1"></i>{{ __('Add') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Items Table (Newest Items Appear on Top) -->
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" id="itemsTable">
              <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                  <th style="width: 45px;" class="text-center">#</th>
                  <th style="min-width: 220px;">{{ __('Vegetable / Item Name') }}</th>
                  <th style="width: 85px;" class="text-center">{{ __('Unit') }}</th>
                  <th style="width: 100px;" class="text-center">{{ __('Stock') }}</th>
                  <th style="width: 120px;" class="text-end">{{ __('Quantity') }}</th>
                  <th style="width: 130px;" class="text-end">{{ __('Rate / Price') }}</th>
                  <th style="width: 140px;" class="text-end">{{ __('Amount') }}</th>
                  <th style="width: 45px;" class="text-center"></th>
                </tr>
              </thead>
              <tbody id="itemsTableBody">
                <!-- If old items exist after validation redirect -->
                @if(old('items') && is_array(old('items')))
                  @foreach(old('items') as $idx => $oldItem)
                    @php
                      $prod = $products->firstWhere('id', $oldItem['product_id'] ?? null);
                      $qty = (float) ($oldItem['quantity'] ?? 0);
                      $rate = (float) ($oldItem['unit_price'] ?? 0);
                      $amt = round($qty * $rate, 2);
                    @endphp
                    @if($prod)
                      <tr class="item-row" data-index="{{ $idx }}">
                        <td class="text-center font-monospace fw-semibold text-muted item-sr-no">{{ $idx + 1 }}</td>
                        <td>
                          <span class="fw-semibold text-dark">{{ $prod->name }} @if($prod->name_ur) ({{ $prod->name_ur }}) @endif</span>
                          <input type="hidden" name="items[{{ $idx }}][product_id]" class="prod-id-input" value="{{ $prod->id }}">
                          <input type="hidden" name="items[{{ $idx }}][unit_id]" class="unit-id-input" value="{{ $oldItem['unit_id'] ?? $prod->stock_unit_id }}">
                        </td>
                        <td class="text-center">
                          <span class="badge bg-light text-dark border px-2 py-1 unit-badge">{{ $prod->stockUnit?->code ?? 'kg' }}</span>
                        </td>
                        <td class="text-center">
                          <span class="small fw-semibold stock-badge text-muted">{{ (float) ($stocks[$prod->id] ?? 0) }} {{ $prod->stockUnit?->code ?? 'kg' }}</span>
                        </td>
                        <td>
                          <input type="number" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-end qty-input font-monospace" step="0.001" min="0.001" value="{{ $qty }}" required>
                        </td>
                        <td>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">{{ $tenant->currency }}</span>
                            <input type="number" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm text-end rate-input font-monospace" step="0.01" min="0" value="{{ $rate }}" required>
                          </div>
                        </td>
                        <td class="text-end fw-bold line-total font-monospace text-primary">
                          {{ $tenant->currency }} {{ number_format($amt, 2) }}
                        </td>
                        <td class="text-center">
                          <button type="button" class="btn btn-outline-danger btn-sm border-0 py-0 px-2 remove-row-btn" title="Remove Item">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    @endif
                  @endforeach
                @endif
              </tbody>
            </table>

            <!-- Empty State Placeholder (Visible when no rows added) -->
            <div id="emptyTableMessage" class="text-center py-4 text-muted border-top @if(old('items') && count(old('items')) > 0) d-none @endif">
              <i class="bi bi-basket3 fs-2 text-secondary opacity-50 d-block mb-1"></i>
              <span class="fw-semibold">{{ __('No produce items added yet.') }}</span>
              <p class="small text-muted mb-0">{{ __('Use the Top Quick-Entry bar above to add items. Each newly added item will appear at the top.') }}</p>
            </div>
          </div>

          <div class="card-footer bg-light py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
              <i class="bi bi-info-circle me-1"></i> {{ __('Items are listed with newest added produce on top.') }}
            </span>
            <span class="text-muted small">
              {{ __('Total Line Items: ') }} <strong id="totalItemCountBottom">0</strong>
            </span>
          </div>
        </div>

        <!-- Notes / Delivery Narration -->
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
                <input type="number" name="delivery_charges" id="deliveryChargesInput" class="form-control text-end font-monospace" step="0.01" min="0" value="{{ old('delivery_charges', '0.00') }}">
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
                <input type="number" name="discount_amount" id="discountInput" class="form-control text-end font-monospace" step="0.01" min="0" value="{{ old('discount_amount', '0.00') }}">
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
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold text-dark mb-0">
                  {{ __('Cash Paid on Spot (Optional):') }}
                </label>
                <kbd class="bg-light text-dark border small">F8</kbd>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-success fw-bold">{{ $tenant->currency }}</span>
                <input type="number" name="paid_amount" id="paidAmountInput" class="form-control text-end fw-bold text-success font-monospace" step="0.01" min="0" value="{{ old('paid_amount', '0.00') }}" placeholder="0.00">
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

  <!-- JavaScript for Keyboard-First Fast Entry, Top Prepending, and Financial Summary -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const currency = "{{ $tenant->currency }}";

      // Form & Customer Elements
      const creditSaleForm = document.getElementById('creditSaleForm');
      const customerSelect = document.getElementById('customerSelect');
      const customerKhataInfo = document.getElementById('customerKhataInfo');
      const custCurrentBalance = document.getElementById('custCurrentBalance');
      const custCreditLimit = document.getElementById('custCreditLimit');

      // Top Quick-Entry Elements
      const quickProductSelect = document.getElementById('quickProductSelect');
      const quickQtyInput = document.getElementById('quickQtyInput');
      const quickRateInput = document.getElementById('quickRateInput');
      const quickLineTotal = document.getElementById('quickLineTotal');
      const quickUnitBadge = document.getElementById('quickUnitBadge');
      const quickStockBadge = document.getElementById('quickStockBadge');
      const quickAddBtn = document.getElementById('quickAddBtn');

      // Table Elements
      const itemsTable = document.getElementById('itemsTable');
      const itemsTableBody = document.getElementById('itemsTableBody');
      const emptyTableMessage = document.getElementById('emptyTableMessage');
      const totalItemCount = document.getElementById('totalItemCount');
      const totalItemCountBottom = document.getElementById('totalItemCountBottom');

      // Sidebar Totals Elements
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

      // Quick Customer Enter key: jump straight to produce search
      customerSelect.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          quickProductSelect.focus();
        }
      });

      // 2. Quick-Entry Live Line Calculation
      function updateQuickLineTotal() {
        const qty = parseFloat(quickQtyInput.value) || 0;
        const rate = parseFloat(quickRateInput.value) || 0;
        const total = Math.round((qty * rate) * 100) / 100;
        quickLineTotal.textContent = currency + ' ' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
      }

      quickQtyInput.addEventListener('input', updateQuickLineTotal);
      quickRateInput.addEventListener('input', updateQuickLineTotal);

      // On selecting product in Quick Entry Row
      quickProductSelect.addEventListener('change', function () {
        const opt = quickProductSelect.options[quickProductSelect.selectedIndex];
        if (opt && opt.value) {
          const price = parseFloat(opt.dataset.price || 0);
          const unitCode = opt.dataset.unitCode || 'kg';
          const stock = parseFloat(opt.dataset.stock || 0);

          quickRateInput.value = price > 0 ? price.toFixed(2) : '';
          quickUnitBadge.textContent = unitCode;

          quickStockBadge.textContent = "{{ __('Stock: ') }}" + stock.toFixed(1) + ' ' + unitCode;
          if (stock <= 0) {
            quickStockBadge.className = 'small fw-bold text-danger';
          } else if (stock <= 10) {
            quickStockBadge.className = 'small fw-bold text-warning';
          } else {
            quickStockBadge.className = 'small fw-bold text-success';
          }

          updateQuickLineTotal();

          // Smoothly advance focus directly to quantity input for rapid zero-click entry
          setTimeout(function () {
            quickQtyInput.focus();
            quickQtyInput.select();
          }, 30);
        } else {
          quickRateInput.value = '';
          quickUnitBadge.textContent = 'kg';
          quickStockBadge.textContent = "{{ __('Stock: -') }}";
          quickStockBadge.className = 'small text-muted';
          updateQuickLineTotal();
        }
      });

      // Keyboard Flow inside Quick Entry Row
      quickProductSelect.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          if (quickProductSelect.value) {
            e.preventDefault();
            quickQtyInput.focus();
            quickQtyInput.select();
          }
        }
      });

      quickQtyInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          quickRateInput.focus();
          quickRateInput.select();
        }
      });

      quickRateInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          addQuickItemToTop();
        }
      });

      quickAddBtn.addEventListener('click', function () {
        addQuickItemToTop();
      });

      // 3. Add Item Function: PREPENDS TO TOP OF TABLE LIST
      function addQuickItemToTop() {
        const opt = quickProductSelect.options[quickProductSelect.selectedIndex];
        if (!opt || !opt.value) {
          quickProductSelect.focus();
          quickProductSelect.classList.add('is-invalid');
          setTimeout(() => quickProductSelect.classList.remove('is-invalid'), 1500);
          return;
        }

        const qty = parseFloat(quickQtyInput.value);
        if (!qty || qty <= 0) {
          quickQtyInput.focus();
          quickQtyInput.classList.add('is-invalid');
          setTimeout(() => quickQtyInput.classList.remove('is-invalid'), 1500);
          return;
        }

        const rate = parseFloat(quickRateInput.value);
        if (isNaN(rate) || rate < 0) {
          quickRateInput.focus();
          quickRateInput.classList.add('is-invalid');
          setTimeout(() => quickRateInput.classList.remove('is-invalid'), 1500);
          return;
        }

        const prodId = opt.value;
        const prodName = opt.dataset.name || opt.text;
        const unitId = opt.dataset.unitId || '';
        const unitCode = opt.dataset.unitCode || 'kg';
        const stock = parseFloat(opt.dataset.stock || 0);
        const lineAmt = Math.round((qty * rate) * 100) / 100;

        // Build new table row
        const tr = document.createElement('tr');
        tr.className = 'item-row table-primary';
        tr.innerHTML = `
          <td class="text-center font-monospace fw-semibold text-muted item-sr-no">1</td>
          <td>
            <span class="fw-semibold text-dark">${prodName}</span>
            <input type="hidden" class="prod-id-input" value="${prodId}">
            <input type="hidden" class="unit-id-input" value="${unitId}">
          </td>
          <td class="text-center">
            <span class="badge bg-light text-dark border px-2 py-1 unit-badge">${unitCode}</span>
          </td>
          <td class="text-center">
            <span class="small fw-semibold stock-badge ${stock <= 0 ? 'text-danger' : (stock <= 10 ? 'text-warning' : 'text-success')}">
              ${stock.toFixed(1)} ${unitCode}
            </span>
          </td>
          <td>
            <input type="number" class="form-control form-control-sm text-end qty-input font-monospace fw-semibold" step="0.001" min="0.001" value="${qty}" required>
          </td>
          <td>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light text-muted">${currency}</span>
              <input type="number" class="form-control form-control-sm text-end rate-input font-monospace fw-semibold" step="0.01" min="0" value="${rate.toFixed(2)}" required>
            </div>
          </td>
          <td class="text-end fw-bold line-total font-monospace text-primary">
            ${currency} ${lineAmt.toLocaleString('en-US', { minimumFractionDigits: 2 })}
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm border-0 py-0 px-2 remove-row-btn" title="Remove Item">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        `;

        // Prepend new row to the very TOP of the table body
        if (itemsTableBody.firstElementChild) {
          itemsTableBody.insertBefore(tr, itemsTableBody.firstElementChild);
        } else {
          itemsTableBody.appendChild(tr);
        }

        // Attach event listeners to the new row
        attachRowEventListeners(tr);

        // Re-index all rows (Serial #1 at top, #2, #3 below; input names items[0], items[1]...)
        reindexAllRows();

        // Recalculate totals
        recalculateTotals();

        // Brief flash animation to confirm top placement
        setTimeout(() => tr.classList.remove('table-primary'), 800);

        // Clear quick entry inputs for next item
        quickProductSelect.value = '';
        quickQtyInput.value = '';
        quickRateInput.value = '';
        quickLineTotal.textContent = currency + ' 0.00';
        quickUnitBadge.textContent = 'kg';
        quickStockBadge.textContent = "{{ __('Stock: -') }}";
        quickStockBadge.className = 'small text-muted';

        // Return focus immediately to Product selector for continuous rapid entry
        quickProductSelect.focus();
      }

      // 4. Attach Event Listeners to an Item Row
      function attachRowEventListeners(row) {
        const qtyInput = row.querySelector('.qty-input');
        const rateInput = row.querySelector('.rate-input');
        const lineTotal = row.querySelector('.line-total');
        const removeBtn = row.querySelector('.remove-row-btn');

        function updateRowCalculation() {
          const q = parseFloat(qtyInput.value) || 0;
          const r = parseFloat(rateInput.value) || 0;
          const amt = Math.round((q * r) * 100) / 100;
          lineTotal.textContent = currency + ' ' + amt.toLocaleString('en-US', { minimumFractionDigits: 2 });
          recalculateTotals();
        }

        qtyInput.addEventListener('input', updateRowCalculation);
        rateInput.addEventListener('input', updateRowCalculation);

        removeBtn.addEventListener('click', function () {
          row.remove();
          reindexAllRows();
          recalculateTotals();
        });
      }

      // Attach to any existing rows (e.g. from validation error restore)
      document.querySelectorAll('#itemsTableBody .item-row').forEach(attachRowEventListeners);

      // 5. Re-index All Rows in itemsTableBody
      function reindexAllRows() {
        const rows = itemsTableBody.querySelectorAll('.item-row');
        rows.forEach(function (row, idx) {
          // Serial number: 1, 2, 3...
          const srCol = row.querySelector('.item-sr-no');
          if (srCol) srCol.textContent = idx + 1;

          // Input names formatted for Laravel CreditSaleController::store
          const prodId = row.querySelector('.prod-id-input');
          if (prodId) prodId.name = `items[${idx}][product_id]`;

          const unitId = row.querySelector('.unit-id-input');
          if (unitId) unitId.name = `items[${idx}][unit_id]`;

          const qtyInput = row.querySelector('.qty-input');
          if (qtyInput) qtyInput.name = `items[${idx}][quantity]`;

          const rateInput = row.querySelector('.rate-input');
          if (rateInput) rateInput.name = `items[${idx}][unit_price]`;
        });

        // Update counts
        const count = rows.length;
        totalItemCount.textContent = count;
        totalItemCountBottom.textContent = count;

        // Toggle Empty Message
        if (count === 0) {
          emptyTableMessage.classList.remove('d-none');
        } else {
          emptyTableMessage.classList.add('d-none');
        }
      }

      // Initial index check
      reindexAllRows();

      // 6. Overall Financial Calculations & Customer Khata Impact
      function recalculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('#itemsTableBody .item-row').forEach(function (row) {
          const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
          const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
          subtotal += Math.round((qty * rate) * 100) / 100;
        });

        const delivery = parseFloat(deliveryChargesInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const grandTotal = Math.max(0, subtotal + delivery - discount);

        const paid = Math.min(grandTotal, parseFloat(paidAmountInput.value) || 0);
        const due = Math.max(0, grandTotal - paid);

        // Toggle Financial Account dropdown
        if (paid > 0) {
          financialAccountGroup.style.display = 'block';
          accountSelect.required = true;
        } else {
          financialAccountGroup.style.display = 'none';
          accountSelect.required = false;
        }

        // Ledger calculation
        const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
        const prevBal = (selectedOpt && selectedOpt.value) ? parseFloat(selectedOpt.dataset.balance || 0) : 0;
        const newProjectedBal = prevBal + due;

        // Update display text
        subtotalDisplay.textContent = currency + ' ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
        grandTotalDisplay.textContent = currency + ' ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
        dueAmountDisplay.textContent = currency + ' ' + due.toLocaleString('en-US', { minimumFractionDigits: 2 });
        projectedBalanceDisplay.textContent = currency + ' ' + newProjectedBal.toLocaleString('en-US', { minimumFractionDigits: 2 });
      }

      deliveryChargesInput.addEventListener('input', recalculateTotals);
      discountInput.addEventListener('input', recalculateTotals);
      paidAmountInput.addEventListener('input', recalculateTotals);
      recalculateTotals();

      // 7. Global Keyboard Shortcuts
      document.addEventListener('keydown', function (e) {
        // Ctrl + Enter or Cmd + Enter: Save & Submit Bill
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
          e.preventDefault();
          creditSaleForm.requestSubmit();
          return;
        }

        // F2 or Alt + A: Jump to Produce Search
        if (e.key === 'F2' || (e.altKey && (e.key === 'a' || e.key === 'A'))) {
          e.preventDefault();
          quickProductSelect.focus();
          return;
        }

        // F4 or Alt + C: Jump to Customer
        if (e.key === 'F4' || (e.altKey && (e.key === 'c' || e.key === 'C'))) {
          e.preventDefault();
          customerSelect.focus();
          return;
        }

        // F8 or Alt + P: Jump to Paid Amount
        if (e.key === 'F8' || (e.altKey && (e.key === 'p' || e.key === 'P'))) {
          e.preventDefault();
          paidAmountInput.focus();
          paidAmountInput.select();
          return;
        }
      });

      // 8. Form Submit Validation
      creditSaleForm.addEventListener('submit', function (e) {
        // If user typed an item in quick row but didn't press Add before submitting, auto-add it!
        if (quickProductSelect.value && parseFloat(quickQtyInput.value) > 0) {
          addQuickItemToTop();
        }

        const rows = itemsTableBody.querySelectorAll('.item-row');
        if (rows.length === 0) {
          e.preventDefault();
          alert("{{ __('Please add at least one produce item to the bill before recording.') }}");
          quickProductSelect.focus();
          return false;
        }

        if (!customerSelect.value) {
          e.preventDefault();
          alert("{{ __('Please select a credit customer.') }}");
          customerSelect.focus();
          return false;
        }
      });
    });
  </script>
@endsection
