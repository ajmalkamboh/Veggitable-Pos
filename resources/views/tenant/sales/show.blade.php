@extends('layouts.admin')

@section('title', __('Sales Invoice') . ' - ' . $salesInvoice->invoice_no)

@section('content')
  <x-breadcrumb 
    :title="$salesInvoice->invoice_no" 
    :items="[
      __('POS & Sales') => route('tenant.sales.index'),
      __('Invoices') => route('tenant.sales.index'),
      $salesInvoice->invoice_no => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Invoices') }}</span>
      </a>
      <a href="{{ route('tenant.pos.receipt', $salesInvoice) }}" target="_blank" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>{{ __('Thermal Receipt') }}</span>
      </a>
      <button type="button" class="btn btn-warning btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#salesReturnModal">
        <i class="bi bi-arrow-return-left"></i>
        <span>{{ __('Return / Refund Produce') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4 mb-4">
    <!-- Meta Details Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-info-circle me-2"></i>{{ __('Invoice Summary') }}
          </h6>
        </div>
        <div class="card-body">
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>{{ __('Invoice #:') }}</strong> <span class="font-monospace fw-bold">{{ $salesInvoice->invoice_no }}</span></li>
            <li class="mb-2"><strong>{{ __('Date:') }}</strong> {{ $salesInvoice->date->format('d M, Y') }} ({{ $salesInvoice->created_at->format('H:i') }})</li>
            <li class="mb-2"><strong>{{ __('Branch:') }}</strong> {{ $salesInvoice->branch->name ?? '-' }}</li>
            <li class="mb-2"><strong>{{ __('Cashier:') }}</strong> {{ $salesInvoice->creator->name ?? 'Staff' }}</li>
            <li class="mb-2">
              <strong>{{ __('Payment Status:') }}</strong>
              @if($salesInvoice->payment_status === 'paid')
                <span class="badge bg-success">{{ __('Paid') }}</span>
              @elseif($salesInvoice->payment_status === 'partial')
                <span class="badge bg-warning text-dark">{{ __('Partial') }}</span>
              @else
                <span class="badge bg-danger">{{ __('Unpaid') }}</span>
              @endif
            </li>
            <li class="mb-0"><strong>{{ __('Payment Method:') }}</strong> <span class="badge bg-light text-dark border">{{ ucfirst($salesInvoice->payment_method) }}</span></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Customer Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-person me-2"></i>{{ __('Customer Details') }}
          </h6>
        </div>
        <div class="card-body">
          @if($salesInvoice->customer)
            <h5 class="fw-bold mb-1">
              <a href="{{ route('tenant.customers.show', $salesInvoice->customer) }}" class="text-decoration-none text-dark">
                {{ $salesInvoice->customer->name }}
              </a>
            </h5>
            <div class="small text-muted mb-2"><i class="bi bi-telephone me-1"></i>{{ $salesInvoice->customer->phone }}</div>
            <div class="p-2 bg-light rounded border small">
              <div class="d-flex justify-content-between mb-1">
                <span>{{ __('Current Balance (Khata):') }}</span>
                <span class="fw-bold {{ $salesInvoice->customer->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                  {{ $tenant->currency }} {{ number_format($salesInvoice->customer->current_balance, 2) }}
                </span>
              </div>
              @if($salesInvoice->customer->credit_limit > 0)
                <div class="d-flex justify-content-between">
                  <span>{{ __('Credit Limit:') }}</span>
                  <span>{{ $tenant->currency }} {{ number_format($salesInvoice->customer->credit_limit, 2) }}</span>
                </div>
              @endif
            </div>
          @else
            <div class="text-muted py-3 text-center">
              <i class="bi bi-person-slash fs-2 d-block mb-1 text-secondary"></i>
              {{ __('Walk-in Retail Counter Customer (Cash)') }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Profitability & Financial Summary Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-cash-stack me-2"></i>{{ __('Financials & Margins') }}
          </h6>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-borderless mb-0">
            <tbody>
              <tr>
                <td class="ps-3 py-1 text-muted">{{ __('Subtotal:') }}</td>
                <td class="pe-3 py-1 text-end font-monospace">{{ $tenant->currency }} {{ number_format($salesInvoice->subtotal, 2) }}</td>
              </tr>
              @if($salesInvoice->discount_amount > 0)
                <tr>
                  <td class="ps-3 py-1 text-success">{{ __('Discount:') }}</td>
                  <td class="pe-3 py-1 text-end text-success font-monospace">- {{ $tenant->currency }} {{ number_format($salesInvoice->discount_amount, 2) }}</td>
                </tr>
              @endif
              <tr class="table-light border-top">
                <td class="ps-3 py-2 fw-bold">{{ __('Total Amount:') }}</td>
                <td class="pe-3 py-2 text-end fw-bold font-monospace fs-6 text-primary">{{ $tenant->currency }} {{ number_format($salesInvoice->total_amount, 2) }}</td>
              </tr>
              <tr>
                <td class="ps-3 py-1 text-muted">{{ __('Paid Amount:') }}</td>
                <td class="pe-3 py-1 text-end text-success font-monospace fw-semibold">{{ $tenant->currency }} {{ number_format($salesInvoice->paid_amount, 2) }}</td>
              </tr>
              @if($salesInvoice->due_amount > 0)
                <tr class="table-danger">
                  <td class="ps-3 py-1 fw-bold text-danger">{{ __('Due (Receivable):') }}</td>
                  <td class="pe-3 py-1 text-end fw-bold font-monospace text-danger">{{ $tenant->currency }} {{ number_format($salesInvoice->due_amount, 2) }}</td>
                </tr>
              @endif
              <tr class="border-top small text-muted">
                <td class="ps-3 py-2">{{ __('COGS (FIFO Cost):') }}</td>
                <td class="pe-3 py-2 text-end font-monospace">{{ $tenant->currency }} {{ number_format($salesInvoice->cost_amount, 2) }}</td>
              </tr>
              <tr class="small">
                <td class="ps-3 py-1 fw-bold text-success">{{ __('Gross Profit Margin:') }}</td>
                <td class="pe-3 py-1 text-end fw-bold font-monospace text-success">
                  + {{ $tenant->currency }} {{ number_format($salesInvoice->profit_amount, 2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Produce Items Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold text-dark">
        <i class="bi bi-basket3 me-2 text-success"></i>{{ __('Billed Produce Items') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>{{ __('Produce') }}</th>
            <th class="text-center">{{ __('Weighed Qty / Weight') }}</th>
            <th class="text-end">{{ __('Selling Rate') }}</th>
            <th class="text-end">{{ __('Line Discount') }}</th>
            <th class="text-end pe-3">{{ __('Total') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($salesInvoice->items as $idx => $item)
            <tr>
              <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
              <td>
                <div class="fw-bold">{{ $item->product->name }}</div>
                @if($item->product->name_ur)
                  <div class="text-success small font-urdu">{{ $item->product->name_ur }}</div>
                @endif
              </td>
              <td class="text-center font-monospace fw-bold">
                {{ number_format($item->quantity, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace">
                {{ $tenant->currency }} {{ number_format($item->unit_price, 2) }}
              </td>
              <td class="text-end font-monospace text-muted">
                {{ $item->discount_amount > 0 ? $tenant->currency . ' ' . number_format($item->discount_amount, 2) : '-' }}
              </td>
              <td class="text-end font-monospace fw-bold pe-3">
                {{ $tenant->currency }} {{ number_format($item->subtotal, 2) }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Sales Returns / Refunds Section if any -->
  @if($salesInvoice->returns && $salesInvoice->returns->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold text-warning">
          <i class="bi bi-arrow-return-left me-2"></i>{{ __('Returns & Refunds on this Invoice') }}
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">{{ __('Return #') }}</th>
              <th>{{ __('Date') }}</th>
              <th>{{ __('Reason') }}</th>
              <th>{{ __('Method') }}</th>
              <th class="text-end pe-3">{{ __('Refund Amount') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($salesInvoice->returns as $ret)
              <tr>
                <td class="ps-3 font-monospace fw-bold text-primary">{{ $ret->return_no }}</td>
                <td>{{ $ret->date->format('d M, Y') }}</td>
                <td>{{ $ret->reason }}</td>
                <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $ret->refund_method)) }}</span></td>
                <td class="text-end pe-3 fw-bold text-danger">
                  - {{ $tenant->currency }} {{ number_format($ret->total_amount, 2) }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  <!-- Return / Refund Modal -->
  <div class="modal fade" id="salesReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form action="{{ route('tenant.sales.returns.store', $salesInvoice) }}" method="POST">
        @csrf
        <div class="modal-content">
          <div class="modal-header bg-warning-subtle">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-arrow-return-left me-2"></i>{{ __('Return Produce & Refund Customer') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Return Date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Refund Method') }} <span class="text-danger">*</span></label>
                <select name="refund_method" class="form-select" required>
                  <option value="cash">{{ __('Cash Refund') }}</option>
                  @if($salesInvoice->customer_id)
                    <option value="credit_adjustment">{{ __('Credit Adjustment (Deduct Customer Khata)') }}</option>
                  @endif
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">{{ __('Refund From Account') }}</label>
                <select name="financial_account_id" class="form-select">
                  <option value="">{{ __('Select Till / Drawer...') }}</option>
                  @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $tenant->currency }} {{ number_format($acc->current_balance, 2) }})</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold text-muted">{{ __('Reason for Return') }} <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control" placeholder="{{ __('e.g., Customer returned spoiled fruit, excess weight weighed') }}" required>
              </div>
            </div>

            <h6 class="fw-bold mb-2">{{ __('Select Produce Items to Return') }}:</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>{{ __('Produce') }}</th>
                    <th class="text-end">{{ __('Sold Qty') }}</th>
                    <th style="width: 140px;">{{ __('Return Qty') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('Unit Price') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($salesInvoice->items as $idx => $item)
                    <tr>
                      <td>
                        <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                        <strong>{{ $item->product->name }}</strong>
                        @if($item->product->name_ur)
                          <span class="text-success small font-urdu ms-1">({{ $item->product->name_ur }})</span>
                        @endif
                      </td>
                      <td class="text-end font-monospace">
                        {{ number_format($item->quantity, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
                      </td>
                      <td>
                        <input type="number" step="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-end font-monospace" value="0" min="0" max="{{ $item->quantity }}" required>
                      </td>
                      <td class="text-end font-monospace">
                        <input type="hidden" name="items[{{ $idx }}][unit_price]" value="{{ $item->unit_price }}">
                        {{ $tenant->currency }} {{ number_format($item->unit_price, 2) }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-warning fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> {{ __('Process Return & Restock') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

