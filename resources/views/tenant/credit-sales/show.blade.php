@extends('layouts.admin')

@section('title', __('Credit Sale Invoice: ') . $salesInvoice->invoice_no)

@section('content')
  <x-breadcrumb 
    :title="__('Credit Sale Bill Details: ') . $salesInvoice->invoice_no" 
    :items="[
      __('In-Shop Operations') => '#',
      __('Credit Sales') => route('tenant.credit-sales.index'),
      $salesInvoice->invoice_no => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.credit-sales.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> {{ __('Back to List') }}
      </a>
      <a href="{{ route('tenant.credit-sales.export-single-csv', $salesInvoice) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Export CSV') }}
      </a>
      <a href="{{ route('tenant.credit-sales.pdf', $salesInvoice) }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-pdf me-1"></i> {{ __('Download PDF') }}
      </a>
      <button type="button" class="btn btn-success btn-sm text-white" data-bs-toggle="modal" data-bs-target="#whatsappModal">
        <i class="bi bi-whatsapp me-1"></i> {{ __('Share on WhatsApp') }}
      </button>
      <a href="{{ route('tenant.credit-sales.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> {{ __('New Entry') }}
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row g-4">
    <!-- Main Invoice Document View -->
    <div class="col-12 col-xl-8">
      <div class="card border-0 shadow-sm" id="printableInvoice">
        <!-- Shop Header & Branding -->
        <div class="card-body p-4 border-bottom">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <img src="{{ asset('assets/img/logo.webp') }}" alt="{{ $tenant->name }}" height="48">
              <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $tenant->name }}</h4>
                <div class="text-muted small">
                  {{ $tenant->address ?? 'Subzi Mandi Wholesale & Retail Center' }}, {{ $tenant->city }}
                </div>
                @if($tenant->phone)
                  <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $tenant->phone }}</div>
                @endif
              </div>
            </div>

            <div class="text-xl-end">
              <span class="badge bg-warning text-dark px-3 py-2 fw-semibold fs-6 mb-1">
                {{ __('CREDIT SALE BILL / بل ادھار') }}
              </span>
              <div class="font-monospace fw-bold fs-5 text-primary">{{ $salesInvoice->invoice_no }}</div>
              <div class="text-muted small"><i class="bi bi-calendar-event me-1"></i>{{ $salesInvoice->date ? $salesInvoice->date->format('d-M-Y') : '' }}</div>
            </div>
          </div>
        </div>

        <!-- Customer & Branch Information -->
        <div class="card-body p-4 bg-light border-bottom">
          <div class="row g-3">
            <div class="col-12 col-md-6 border-end">
              <span class="text-uppercase small fw-bold text-muted d-block mb-1">{{ __('Billed To (Customer):') }}</span>
              <h5 class="fw-bold text-dark mb-1">
                {{ $customer?->name ?? 'Walk-in Credit' }}
                @if($customer?->name_ur)
                  <span class="urdu-font fw-normal text-muted">({{ $customer->name_ur }})</span>
                @endif
              </h5>
              @if($customer?->phone)
                <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>
              @endif
              @if($customer?->address)
                <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $customer->address }}</div>
              @endif
              @if($customer)
                <div class="mt-2">
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    {{ __('Current Total Khata Balance: ') }}{{ $tenant->currency }} {{ number_format($customer->current_balance, 2) }}
                  </span>
                </div>
              @endif
            </div>

            <div class="col-12 col-md-6 ps-md-4">
              <span class="text-uppercase small fw-bold text-muted d-block mb-1">{{ __('Order Metadata:') }}</span>
              <div class="small mb-1">
                <span class="text-muted">{{ __('Branch Outlet: ') }}</span>
                <strong class="text-dark">{{ $salesInvoice->branch?->name ?? '-' }}</strong>
              </div>
              <div class="small mb-1">
                <span class="text-muted">{{ __('Payment Status: ') }}</span>
                @if($salesInvoice->payment_status === 'paid')
                  <span class="badge bg-success-subtle text-success">{{ __('Paid') }}</span>
                @elseif($salesInvoice->payment_status === 'partial')
                  <span class="badge bg-warning-subtle text-warning">{{ __('Partial') }}</span>
                @else
                  <span class="badge bg-danger-subtle text-danger">{{ __('Unpaid (Credit)') }}</span>
                @endif
              </div>
              <div class="small mb-1">
                <span class="text-muted">{{ __('Recorded By: ') }}</span>
                <span class="text-dark">{{ $salesInvoice->creator?->name ?? 'Staff' }}</span>
              </div>
              @if($salesInvoice->notes)
                <div class="small mt-2 p-2 bg-white rounded border">
                  <span class="text-muted fw-semibold">{{ __('Notes: ') }}</span>
                  <span class="text-dark">{{ $salesInvoice->notes }}</span>
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Line Items Table -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
              <thead class="table-light">
                <tr class="small text-uppercase text-muted">
                  <th style="width: 40px;" class="text-center">#</th>
                  <th>{{ __('Produce Item') }}</th>
                  <th class="text-center" style="width: 100px;">{{ __('Unit') }}</th>
                  <th class="text-end" style="width: 130px;">{{ __('Quantity') }}</th>
                  <th class="text-end" style="width: 130px;">{{ __('Rate') }}</th>
                  <th class="text-end" style="width: 150px;">{{ __('Line Total') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($salesInvoice->items as $idx => $item)
                  <tr>
                    <td class="text-center text-muted font-monospace">{{ $idx + 1 }}</td>
                    <td>
                      <div class="fw-semibold text-dark">{{ $item->product?->name ?? 'Produce Item' }}</div>
                      @if($item->product?->name_ur)
                        <div class="urdu-font text-muted small" style="line-height: 1.2;">{{ $item->product->name_ur }}</div>
                      @endif
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border">
                        {{ $item->unit?->name ?? $item->product?->stockUnit?->name ?? 'kg' }}
                      </span>
                    </td>
                    <td class="text-end font-monospace fw-semibold">
                      {{ number_format($item->quantity, 3) }}
                    </td>
                    <td class="text-end font-monospace text-muted">
                      {{ $tenant->currency }} {{ number_format($item->unit_price, 2) }}
                    </td>
                    <td class="text-end font-monospace fw-bold text-dark">
                      {{ $tenant->currency }} {{ number_format($item->subtotal, 2) }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <!-- Totals & Financial Box -->
        <div class="card-body p-4 bg-light">
          <div class="row justify-content-end">
            <div class="col-12 col-md-6 col-lg-5">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">{{ __('Subtotal:') }}</span>
                <span class="font-monospace fw-semibold">{{ $tenant->currency }} {{ number_format($salesInvoice->subtotal, 2) }}</span>
              </div>

              @if($salesInvoice->delivery_charges > 0)
                <div class="d-flex justify-content-between mb-2 text-success">
                  <span><i class="bi bi-truck me-1"></i>{{ __('Delivery / Freight:') }}</span>
                  <span class="font-monospace fw-semibold">+{{ $tenant->currency }} {{ number_format($salesInvoice->delivery_charges, 2) }}</span>
                </div>
              @endif

              @if($salesInvoice->discount_amount > 0)
                <div class="d-flex justify-content-between mb-2 text-danger">
                  <span><i class="bi bi-tag me-1"></i>{{ __('Special Discount:') }}</span>
                  <span class="font-monospace fw-semibold">-{{ $tenant->currency }} {{ number_format($salesInvoice->discount_amount, 2) }}</span>
                </div>
              @endif

              <hr class="my-2">

              <div class="d-flex justify-content-between mb-2 p-2 bg-white rounded border">
                <span class="fw-bold fs-6 text-dark">{{ __('Total Bill Amount:') }}</span>
                <span class="font-monospace fw-bold fs-5 text-primary">{{ $tenant->currency }} {{ number_format($salesInvoice->total_amount, 2) }}</span>
              </div>

              @if($salesInvoice->paid_amount > 0)
                <div class="d-flex justify-content-between mb-2 text-success">
                  <span>{{ __('Paid on Spot (Cash):') }}</span>
                  <span class="font-monospace fw-bold">{{ $tenant->currency }} {{ number_format($salesInvoice->paid_amount, 2) }}</span>
                </div>
              @endif

              <div class="d-flex justify-content-between text-danger p-2 bg-danger-subtle rounded border border-danger-subtle">
                <span class="fw-bold">{{ __('Net Added to Khata (Due):') }}</span>
                <span class="font-monospace fw-bold fs-6">{{ $tenant->currency }} {{ number_format($salesInvoice->due_amount, 2) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Signatures -->
        <div class="card-footer bg-white p-4">
          <div class="row pt-4 text-center">
            <div class="col-6">
              <div class="border-top pt-2 mx-auto" style="max-width: 200px;">
                <span class="small text-muted">{{ __('Customer Signature / دستخط گاہک') }}</span>
              </div>
            </div>
            <div class="col-6">
              <div class="border-top pt-2 mx-auto" style="max-width: 200px;">
                <span class="small text-muted">{{ __('Authorized Signatory / دکاندار') }}</span>
              </div>
            </div>
          </div>
          <div class="text-center mt-4">
            <small class="text-muted">{{ __('تشریف لانے کا شکریہ! تازہ مال روزانہ دستیاب') }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Share & Khata Ledger Side Panel -->
    <div class="col-12 col-xl-4">
      <!-- WhatsApp 1-Click Share Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white py-3 d-flex align-items-center justify-content-between">
          <h6 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-whatsapp fs-5"></i>
            <span>{{ __('WhatsApp Bill Share') }}</span>
          </h6>
          <span class="badge bg-white text-success fw-bold">{{ __('Instant') }}</span>
        </div>
        <div class="card-body">
          <p class="small text-muted mb-2">
            {{ __('Send an itemized bill receipt with current and previous khata balances directly to the customer on WhatsApp.') }}
          </p>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Recipient Phone:') }}</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-telephone text-success"></i></span>
              <input type="text" class="form-control font-monospace" value="{{ $customer?->phone ?? $customer?->mobile ?? 'No phone saved' }}" readonly>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">{{ __('Message Preview:') }}</label>
            <textarea class="form-control form-control-sm font-monospace bg-light" rows="8" id="whatsAppTextPreview" readonly>{{ $whatsAppMessage }}</textarea>
          </div>

          <div class="d-grid gap-2">
            <a href="{{ $whatsAppUrl }}" target="_blank" class="btn btn-success py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-white">
              <i class="bi bi-whatsapp fs-5"></i>
              <span>{{ __('Send via WhatsApp') }}</span>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2" id="copyMsgBtn" onclick="copyWhatsAppMessage()">
              <i class="bi bi-clipboard"></i>
              <span id="copyBtnText">{{ __('Copy Message to Clipboard') }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Export & Print Actions Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
          <h6 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-gear-wide-connected text-primary me-2"></i>{{ __('Actions & Downloads') }}
          </h6>
        </div>
        <div class="card-body d-grid gap-2">
          <a href="{{ route('tenant.credit-sales.pdf', $salesInvoice) }}" class="btn btn-outline-danger d-flex align-items-center justify-content-between p-2">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-pdf fs-5 text-danger"></i>
              <div class="text-start">
                <strong class="d-block text-dark small">{{ __('Download Official PDF Bill') }}</strong>
                <span class="text-muted" style="font-size: 0.75rem;">A4 formatted invoice with Urdu typography</span>
              </div>
            </div>
            <i class="bi bi-download"></i>
          </a>

          <a href="{{ route('tenant.credit-sales.export-single-csv', $salesInvoice) }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-2">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-spreadsheet fs-5 text-success"></i>
              <div class="text-start">
                <strong class="d-block text-dark small">{{ __('Export Items CSV (Excel)') }}</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Structured spreadsheet format</span>
              </div>
            </div>
            <i class="bi bi-download"></i>
          </a>

          <button type="button" class="btn btn-outline-dark d-flex align-items-center justify-content-between p-2" onclick="window.print()">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-printer fs-5"></i>
              <div class="text-start">
                <strong class="d-block text-dark small">{{ __('Browser Direct Print') }}</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Quick thermal or desktop printer</span>
              </div>
            </div>
            <i class="bi bi-chevron-right"></i>
          </button>

          @if($customer)
            <a href="{{ route('tenant.customers.show', $customer) }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-2 mt-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-journal-text fs-5 text-primary"></i>
                <div class="text-start">
                  <strong class="d-block text-dark small">{{ __('Customer Khata Statement') }}</strong>
                  <span class="text-muted" style="font-size: 0.75rem;">View complete debit/credit ledger</span>
                </div>
              </div>
              <i class="bi bi-arrow-right"></i>
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- WhatsApp Modal -->
  <div class="modal fade" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white">
          <h6 class="modal-title fw-bold" id="whatsappModalLabel">
            <i class="bi bi-whatsapp me-2"></i>{{ __('Share Bill on WhatsApp') }}
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="small text-muted mb-2">{{ __('The following formatted message will be dispatched:') }}</p>
          <pre class="p-3 bg-light rounded-3 small border font-monospace text-wrap" style="max-height: 250px; overflow-y: auto;">{{ $whatsAppMessage }}</pre>
          <div class="alert alert-info py-2 px-3 small mb-0">
            <i class="bi bi-info-circle me-1"></i> {{ __('Make sure WhatsApp is installed or WhatsApp Web is active in your browser.') }}
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Close') }}</button>
          <button type="button" class="btn btn-outline-dark btn-sm" onclick="copyWhatsAppMessage()">
            <i class="bi bi-clipboard me-1"></i> {{ __('Copy Text') }}
          </button>
          <a href="{{ $whatsAppUrl }}" target="_blank" class="btn btn-success btn-sm text-white px-3">
            <i class="bi bi-whatsapp me-1"></i> {{ __('Open WhatsApp') }}
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function copyWhatsAppMessage() {
      const text = document.getElementById('whatsAppTextPreview').value;
      navigator.clipboard.writeText(text).then(function() {
        const btnText = document.getElementById('copyBtnText');
        if (btnText) {
          const original = btnText.textContent;
          btnText.textContent = "{{ __('Copied to Clipboard!') }}";
          setTimeout(() => { btnText.textContent = original; }, 2500);
        } else {
          alert("{{ __('Message copied to clipboard!') }}");
        }
      }).catch(function(err) {
        alert("{{ __('Could not copy text: ') }}" + err);
      });
    }
  </script>
@endsection
