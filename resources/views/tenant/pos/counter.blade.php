@extends('layouts.admin')

@section('title', __('POS Cashier Counter') . ' - ' . $branch->name)

@section('content')
<div class="container-fluid px-0">
  <!-- POS Top Bar -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm gap-2">
    <div class="d-flex align-items-center gap-3">
      <div class="bg-success text-white p-2 rounded fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
        <i class="bi bi-cart4"></i>
      </div>
      <div>
        <h5 class="mb-0 fw-bold">{{ __('Retail POS Cashier') }}</h5>
        <span class="badge bg-light text-dark border">
          <i class="bi bi-shop me-1 text-primary"></i>{{ $branch->name }}
        </span>
        @if($posSession)
          <span class="badge bg-success-subtle text-success border border-success ms-1">
            <i class="bi bi-unlock me-1"></i>{{ __('Till Open:') }} {{ $posSession->session_no }}
          </span>
        @else
          <span class="badge bg-danger-subtle text-danger border border-danger ms-1">
            <i class="bi bi-lock me-1"></i>{{ __('Till Closed') }}
          </span>
        @endif
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-outline-warning btn-sm" id="btnResumeModal" data-bs-toggle="modal" data-bs-target="#heldTicketsModal">
        <i class="bi bi-pause-circle me-1"></i>{{ __('Parked Carts') }}
        <span class="badge bg-warning text-dark ms-1" id="heldBadge">{{ $heldCount }}</span>
      </button>

      @if($posSession)
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closeSessionModal">
          <i class="bi bi-x-circle me-1"></i>{{ __('Close Till') }}
        </button>
      @else
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#openSessionModal">
          <i class="bi bi-key me-1"></i>{{ __('Open Till Session') }}
        </button>
      @endif

      <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-receipt me-1"></i>{{ __('Sales Log') }}
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-3">
    <!-- LEFT PANEL: Produce Catalog & Category Filter -->
    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <!-- Search & Barcode Input -->
          <div class="row g-2 mb-3">
            <div class="col-8">
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="posSearchInput" class="form-control" placeholder="{{ __('Search vegetable or fruit by name, code...') }}" autocomplete="off">
              </div>
            </div>
            <div class="col-4">
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                <input type="text" id="barcodeScannerInput" class="form-control font-monospace" placeholder="{{ __('Scan Barcode') }}" autofocus>
              </div>
            </div>
          </div>

          <!-- Category Filter Pills -->
          <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="white-space: nowrap;">
            <button type="button" class="btn btn-sm btn-primary category-pill" data-category="">
              {{ __('All Produce') }}
            </button>
            @foreach($categories as $cat)
              <button type="button" class="btn btn-sm btn-outline-secondary category-pill" data-category="{{ $cat->id }}">
                {{ $cat->name }} {{ $cat->name_ur ? '(' . $cat->name_ur . ')' : '' }}
              </button>
            @endforeach
          </div>

          <!-- Produce Items Grid -->
          <div class="row g-2 overflow-auto" id="produceGrid" style="max-height: 560px;">
            @foreach($products as $prod)
              <div class="col-6 col-sm-4 col-md-3 produce-card-wrapper" 
                   data-id="{{ $prod->id }}" 
                   data-name="{{ $prod->name }}" 
                   data-name-ur="{{ $prod->name_ur }}" 
                   data-code="{{ $prod->code }}" 
                   data-barcode="{{ $prod->barcode }}" 
                   data-price="{{ $prod->selling_price }}" 
                   data-unit="{{ $prod->stockUnit->short_name ?? 'kg' }}"
                   data-category="{{ $prod->category_id }}">
                <div class="card h-100 border text-center p-2 produce-card cursor-pointer shadow-none hover-shadow" 
                     style="transition: all 0.15s ease-in-out; cursor: pointer;">
                  <div class="fw-bold text-dark text-truncate small mb-0">{{ $prod->name }}</div>
                  @if($prod->name_ur)
                    <div class="text-success extra-small font-urdu text-truncate">{{ $prod->name_ur }}</div>
                  @endif
                  <div class="mt-2">
                    <span class="badge bg-success-subtle text-success fs-6 fw-bold font-monospace">
                      {{ $tenant->currency }} {{ number_format($prod->selling_price, 2) }}
                    </span>
                    <span class="text-muted extra-small d-block">/ {{ $prod->stockUnit->short_name ?? 'kg' }}</span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: Current Cart Ticket & Checkout -->
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm h-100 d-flex flex-column">
        <!-- Cart Header: Customer Selection -->
        <div class="card-header bg-white border-bottom p-3">
          <div class="row g-2 align-items-center">
            <div class="col-8">
              <select id="cartCustomerSelect" class="form-select form-select-sm">
                <option value="">{{ __('Walk-in Retail Customer (Cash)') }}</option>
                @foreach($customers as $c)
                  <option value="{{ $c->id }}" data-balance="{{ $c->current_balance }}" data-limit="{{ $c->credit_limit }}">
                    {{ $c->name }} ({{ $c->phone }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-4 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btnClearCart" title="{{ __('Clear Cart') }}">
                <i class="bi bi-trash me-1"></i>{{ __('Clear') }}
              </button>
            </div>
          </div>
          <!-- Customer Credit Info Alert -->
          <div id="customerCreditAlert" class="mt-2 small text-muted d-none p-2 bg-light border rounded">
            <span id="customerCreditText"></span>
          </div>
        </div>

        <!-- Cart Items Table Container -->
        <div class="card-body p-0 flex-grow-1 overflow-auto" style="max-height: 280px;">
          <table class="table table-sm table-hover align-middle mb-0" id="cartTable">
            <thead class="table-light sticky-top">
              <tr class="small text-muted">
                <th class="ps-3">{{ __('Produce') }}</th>
                <th style="width: 110px;" class="text-center">{{ __('Scale Wt / Qty') }}</th>
                <th style="width: 80px;" class="text-end">{{ __('Rate') }}</th>
                <th style="width: 80px;" class="text-end">{{ __('Total') }}</th>
                <th style="width: 35px;"></th>
              </tr>
            </thead>
            <tbody id="cartTableBody">
              <tr id="emptyCartRow">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-basket3 fs-1 text-secondary d-block mb-1"></i>
                  {{ __('Cart is empty. Click produce or scan barcode to add.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Cart Calculations & Checkout Footer -->
        <div class="card-footer bg-white border-top p-3">
          <!-- Subtotal & Discount -->
          <div class="d-flex justify-content-between mb-1 small text-muted">
            <span>{{ __('Subtotal') }}:</span>
            <span class="fw-semibold font-monospace" id="displaySubtotal">{{ $tenant->currency }} 0.00</span>
          </div>

          <div class="row g-2 align-items-center mb-2">
            <div class="col-6 small text-muted">
              <span>{{ __('Discount') }}:</span>
            </div>
            <div class="col-6">
              <div class="input-group input-group-sm">
                <span class="input-group-text">{{ $tenant->currency }}</span>
                <input type="number" step="0.5" id="cartDiscountInput" class="form-control text-end font-monospace" value="0.00" min="0">
              </div>
            </div>
          </div>

          <!-- Grand Total Banner -->
          <div class="p-2 rounded bg-success-subtle text-success border border-success d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold">{{ __('PAYABLE TOTAL:') }}</span>
            <span class="fs-4 fw-bold font-monospace" id="displayGrandTotal">{{ $tenant->currency }} 0.00</span>
          </div>

          <!-- Payment Method Selector -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label extra-small fw-semibold text-muted mb-1">{{ __('Payment Method') }}</label>
              <select id="paymentMethodSelect" class="form-select form-select-sm">
                <option value="cash" selected>{{ __('Cash') }}</option>
                <option value="credit">{{ __('Customer Credit (Khata)') }}</option>
                <option value="bank_card">{{ __('Bank Card / POS') }}</option>
                <option value="qr_transfer">{{ __('Online QR / Transfer') }}</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label extra-small fw-semibold text-muted mb-1">{{ __('Cash Tendered') }}</label>
              <input type="number" step="1" id="cashTenderedInput" class="form-control form-control-sm text-end font-monospace" placeholder="0.00">
            </div>
          </div>

          <!-- Change Due Display -->
          <div class="d-flex justify-content-between mb-3 p-2 bg-light rounded border small">
            <span class="text-muted">{{ __('Change Due:') }}</span>
            <span class="fw-bold text-primary font-monospace" id="displayChangeDue">{{ $tenant->currency }} 0.00</span>
          </div>

          <!-- POS Buttons: Checkout, Hold -->
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning flex-shrink-0" id="btnHoldCart" title="{{ __('Park Cart (F4)') }}">
              <i class="bi bi-pause-circle me-1"></i>{{ __('Hold') }}
            </button>
            <button type="button" class="btn btn-success flex-grow-1 py-2 fs-6 fw-bold" id="btnCheckout">
              <i class="bi bi-check2-circle me-1"></i>{{ __('Complete Checkout (F8)') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Parked / Held Carts -->
<div class="modal fade" id="heldTicketsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-pause-circle me-2"></i>{{ __('Parked / Held Sales Tickets') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Ticket #') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Parked Time') }}</th>
                <th class="text-end">{{ __('Total') }}</th>
                <th class="text-end pe-3">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody id="heldTicketsTableBody">
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">{{ __('Loading parked tickets...') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Open Till Session -->
<div class="modal fade" id="openSessionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('tenant.pos.session.open') }}" method="POST">
      @csrf
      <input type="hidden" name="branch_id" value="{{ $branch->id }}">
      <div class="modal-content">
        <div class="modal-header bg-success-subtle">
          <h5 class="modal-title fw-bold">{{ __('Open Cash Till Session') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Opening Cash Float') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">{{ $tenant->currency }}</span>
              <input type="number" step="1" name="opening_cash" class="form-control" value="0.00" min="0" required>
            </div>
            <small class="text-muted">{{ __('Cash amount physically present in drawer at opening.') }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Notes') }}</label>
            <input type="text" name="notes" class="form-control" placeholder="{{ __('e.g., Morning shift counter') }}">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-success fw-semibold">{{ __('Open Till') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Close Till Session -->
@if($posSession)
<div class="modal fade" id="closeSessionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('tenant.pos.session.close', $posSession) }}" method="POST">
      @csrf
      <div class="modal-content">
        <div class="modal-header bg-danger-subtle">
          <h5 class="modal-title fw-bold text-danger">{{ __('Close Cash Till Session') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Closing Physical Cash Count') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">{{ $tenant->currency }}</span>
              <input type="number" step="1" name="closing_cash" class="form-control" required>
            </div>
            <small class="text-muted">{{ __('Physically count and enter all cash notes/coins in till.') }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Notes / Discrepancy Reason') }}</label>
            <input type="text" name="notes" class="form-control">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-danger fw-semibold">{{ __('Close Session & Generate Report') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
  const currency = '{{ $tenant->currency }}';
  let cart = [];
  let currentHeldTicketId = null;

  const produceGrid = document.getElementById('produceGrid');
  const cartTableBody = document.getElementById('cartTableBody');
  const displaySubtotal = document.getElementById('displaySubtotal');
  const displayGrandTotal = document.getElementById('displayGrandTotal');
  const displayChangeDue = document.getElementById('displayChangeDue');
  const discountInput = document.getElementById('cartDiscountInput');
  const cashTenderedInput = document.getElementById('cashTenderedInput');
  const paymentMethodSelect = document.getElementById('paymentMethodSelect');
  const customerSelect = document.getElementById('cartCustomerSelect');
  const barcodeInput = document.getElementById('barcodeScannerInput');
  const searchInput = document.getElementById('posSearchInput');

  // Fast Keyboard Shortcuts (F4: Hold, F8: Checkout)
  document.addEventListener('keydown', function (e) {
    if (e.key === 'F4') {
      e.preventDefault();
      document.getElementById('btnHoldCart').click();
    } else if (e.key === 'F8') {
      e.preventDefault();
      document.getElementById('btnCheckout').click();
    }
  });

  // Barcode Scanning Input Listener
  barcodeInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = this.value.trim();
      if (!code) return;

      const card = produceGrid.querySelector(`[data-barcode="${code}"], [data-code="${code}"]`);
      if (card) {
        addItemToCart(card);
        this.value = '';
      } else {
        alert('{{ __("Product with barcode :code not found.", ["code" => ""]) }}' + code);
        this.value = '';
      }
    }
  });

  // Category Filtering
  document.querySelectorAll('.category-pill').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.category-pill').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-secondary');
      });
      this.classList.remove('btn-outline-secondary');
      this.classList.add('btn-primary');

      const catId = this.getAttribute('data-category');
      document.querySelectorAll('.produce-card-wrapper').forEach(card => {
        if (!catId || card.getAttribute('data-category') === catId) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // Search Filter
  searchInput.addEventListener('input', function () {
    const term = this.value.toLowerCase().trim();
    document.querySelectorAll('.produce-card-wrapper').forEach(card => {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const nameUr = (card.getAttribute('data-name-ur') || '').toLowerCase();
      const code = (card.getAttribute('data-code') || '').toLowerCase();
      const barcode = (card.getAttribute('data-barcode') || '').toLowerCase();

      if (name.includes(term) || nameUr.includes(term) || code.includes(term) || barcode.includes(term)) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  });

  // Add Item Click
  produceGrid.addEventListener('click', function (e) {
    const card = e.target.closest('.produce-card-wrapper');
    if (card) {
      addItemToCart(card);
    }
  });

  function addItemToCart(card) {
    const id = parseInt(card.getAttribute('data-id'));
    const name = card.getAttribute('data-name');
    const nameUr = card.getAttribute('data-name-ur');
    const price = parseFloat(card.getAttribute('data-price')) || 0;
    const unit = card.getAttribute('data-unit') || 'kg';

    const existing = cart.find(i => i.product_id === id);
    if (existing) {
      existing.quantity = Math.round((existing.quantity + 1) * 1000) / 1000;
    } else {
      cart.push({
        product_id: id,
        name: name,
        name_ur: nameUr,
        unit_price: price,
        quantity: 1.0,
        unit: unit,
        discount_amount: 0.00
      });
    }

    renderCart();
  }

  function renderCart() {
    if (cart.length === 0) {
      cartTableBody.innerHTML = `
        <tr id="emptyCartRow">
          <td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-basket3 fs-1 text-secondary d-block mb-1"></i>
            {{ __('Cart is empty. Click produce or scan barcode to add.') }}
          </td>
        </tr>`;
      calculateTotals();
      return;
    }

    let html = '';
    cart.forEach((item, index) => {
      const lineTotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
      html += `
        <tr>
          <td class="ps-3">
            <div class="fw-bold small">${item.name}</div>
            ${item.name_ur ? `<div class="text-success extra-small font-urdu">${item.name_ur}</div>` : ''}
          </td>
          <td>
            <div class="input-group input-group-sm">
              <input type="number" step="0.05" class="form-control text-center font-monospace p-1 cart-qty-input" data-index="${index}" value="${item.quantity}" min="0.001">
              <span class="input-group-text p-1 extra-small">${item.unit}</span>
            </div>
          </td>
          <td class="text-end font-monospace small">
            <input type="number" step="1" class="form-control form-control-sm text-end p-1 font-monospace cart-price-input" data-index="${index}" value="${item.unit_price}" min="0">
          </td>
          <td class="text-end font-monospace fw-bold text-success">
            ${lineTotal.toFixed(2)}
          </td>
          <td class="text-end pe-2">
            <button type="button" class="btn btn-link text-danger p-0 cart-remove-btn" data-index="${index}">
              <i class="bi bi-x-circle"></i>
            </button>
          </td>
        </tr>`;
    });

    cartTableBody.innerHTML = html;
    calculateTotals();
  }

  // Cart Qty, Price, Remove Listeners
  cartTableBody.addEventListener('input', function (e) {
    if (e.target.classList.contains('cart-qty-input')) {
      const idx = parseInt(e.target.getAttribute('data-index'));
      const val = parseFloat(e.target.value) || 0;
      if (val > 0) {
        cart[idx].quantity = val;
        renderCart();
      }
    } else if (e.target.classList.contains('cart-price-input')) {
      const idx = parseInt(e.target.getAttribute('data-index'));
      const val = parseFloat(e.target.value) || 0;
      if (val >= 0) {
        cart[idx].unit_price = val;
        renderCart();
      }
    }
  });

  cartTableBody.addEventListener('click', function (e) {
    const btn = e.target.closest('.cart-remove-btn');
    if (btn) {
      const idx = parseInt(btn.getAttribute('data-index'));
      cart.splice(idx, 1);
      renderCart();
    }
  });

  document.getElementById('btnClearCart').addEventListener('click', function () {
    if (cart.length > 0 && confirm('{{ __("Clear all items from current cart?") }}')) {
      cart = [];
      currentHeldTicketId = null;
      renderCart();
    }
  });

  // Calculate Totals
  function calculateTotals() {
    let subtotal = 0;
    cart.forEach(item => {
      subtotal += Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
    });

    const discount = parseFloat(discountInput.value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);

    displaySubtotal.textContent = `${currency} ${subtotal.toFixed(2)}`;
    displayGrandTotal.textContent = `${currency} ${grandTotal.toFixed(2)}`;

    // If cash, update tendered default
    const tendered = parseFloat(cashTenderedInput.value) || 0;
    const change = Math.max(0, tendered - grandTotal);
    displayChangeDue.textContent = `${currency} ${change.toFixed(2)}`;
  }

  discountInput.addEventListener('input', calculateTotals);
  cashTenderedInput.addEventListener('input', calculateTotals);

  // Customer Credit Limit check
  customerSelect.addEventListener('change', function () {
    const alertBox = document.getElementById('customerCreditAlert');
    const textSpan = document.getElementById('customerCreditText');
    const opt = this.options[this.selectedIndex];

    if (this.value && opt) {
      const balance = parseFloat(opt.getAttribute('data-balance')) || 0;
      const limit = parseFloat(opt.getAttribute('data-limit')) || 0;
      alertBox.classList.remove('d-none');
      textSpan.innerHTML = `<strong>{{ __('Current Balance:') }}</strong> ${currency} ${balance.toFixed(2)} | <strong>{{ __('Credit Limit:') }}</strong> ${limit > 0 ? currency + ' ' + limit.toFixed(2) : '{{ __("No Limit") }}'}`;
    } else {
      alertBox.classList.add('d-none');
    }
  });

  // Checkout Action
  document.getElementById('btnCheckout').addEventListener('click', function () {
    if (cart.length === 0) {
      alert('{{ __("Please add produce items to the cart before checkout.") }}');
      return;
    }

    const subtotal = cart.reduce((sum, i) => sum + ((i.quantity * i.unit_price) - i.discount_amount), 0);
    const discount = parseFloat(discountInput.value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);
    const paymentMethod = paymentMethodSelect.value;
    const customerId = customerSelect.value || null;
    let paidAmount = parseFloat(cashTenderedInput.value) || grandTotal;

    if (paymentMethod === 'credit') {
      paidAmount = 0.00;
      if (!customerId) {
        alert('{{ __("Please select a registered customer for credit sales.") }}');
        return;
      }
    }

    const payload = {
      branch_id: {{ $branch->id }},
      customer_id: customerId,
      payment_method: paymentMethod,
      discount_amount: discount,
      paid_amount: paidAmount,
      held_ticket_id: currentHeldTicketId,
      items: cart.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        unit_price: i.unit_price,
        discount_amount: i.discount_amount
      }))
    };

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("Processing...") }}';

    fetch('{{ route("tenant.pos.checkout") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.open(data.print_url, '_blank', 'width=450,height=600');
        cart = [];
        currentHeldTicketId = null;
        discountInput.value = '0.00';
        cashTenderedInput.value = '';
        renderCart();
      } else {
        alert(data.message || 'Error completing sale');
      }
    })
    .catch(err => {
      alert('Error processing checkout. Please check parameters.');
    })
    .finally(() => {
      document.getElementById('btnCheckout').disabled = false;
      document.getElementById('btnCheckout').innerHTML = '<i class="bi bi-check2-circle me-1"></i>{{ __("Complete Checkout (F8)") }}';
    });
  });

  // Hold / Park Cart Action
  document.getElementById('btnHoldCart').addEventListener('click', function () {
    if (cart.length === 0) {
      alert('{{ __("Cannot hold an empty cart.") }}');
      return;
    }

    const subtotal = cart.reduce((sum, i) => sum + ((i.quantity * i.unit_price) - i.discount_amount), 0);
    const discount = parseFloat(discountInput.value) || 0;
    const total = Math.max(0, subtotal - discount);

    fetch('{{ route("tenant.pos.hold") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        customer_id: customerSelect.value || null,
        cart_data: cart,
        total_amount: total
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        cart = [];
        currentHeldTicketId = null;
        renderCart();
        loadHeldTickets();
      }
    });
  });

  // Load Held Tickets in Modal
  document.getElementById('btnResumeModal').addEventListener('click', loadHeldTickets);

  function loadHeldTickets() {
    fetch('{{ route("tenant.pos.held-tickets") }}')
    .then(res => res.json())
    .then(data => {
      const tbody = document.getElementById('heldTicketsTableBody');
      const badge = document.getElementById('heldBadge');
      badge.textContent = data.tickets.length;

      if (data.tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">{{ __("No parked tickets available.") }}</td></tr>';
        return;
      }

      let html = '';
      data.tickets.forEach(t => {
        html += `
          <tr>
            <td class="ps-3 font-monospace fw-bold text-primary">${t.ticket_number}</td>
            <td>${t.customer ? t.customer.name : '{{ __("Walk-in Customer") }}'}</td>
            <td class="small text-muted">${new Date(t.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</td>
            <td class="text-end font-monospace fw-bold">${currency} ${parseFloat(t.total_amount).toFixed(2)}</td>
            <td class="text-end pe-3">
              <button type="button" class="btn btn-sm btn-success resume-ticket-btn" data-id="${t.id}" data-cart='${JSON.stringify(t.cart_data)}' data-customer="${t.customer_id || ''}">
                <i class="bi bi-play-circle me-1"></i>{{ __('Resume') }}
              </button>
            </td>
          </tr>`;
      });
      tbody.innerHTML = html;
    });
  }

  // Resume Ticket Handler
  document.getElementById('heldTicketsTableBody').addEventListener('click', function (e) {
    const btn = e.target.closest('.resume-ticket-btn');
    if (btn) {
      const cartData = JSON.parse(btn.getAttribute('data-cart'));
      const customerId = btn.getAttribute('data-customer');
      currentHeldTicketId = parseInt(btn.getAttribute('data-id'));

      cart = cartData;
      if (customerId) {
        customerSelect.value = customerId;
      }
      renderCart();

      const modal = bootstrap.Modal.getInstance(document.getElementById('heldTicketsModal'));
      if (modal) modal.hide();
    }
  });
});
</script>
@endsection

