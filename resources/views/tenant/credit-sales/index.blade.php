@extends('layouts.admin')

@section('title', __('Credit Customer Sales (ادھار فروخت لاگ)'))

@section('content')
  <x-breadcrumb 
    :title="__('Credit Customer Sales & Invoices (ادھار کھاتہ فروخت)')" 
    :items="[
      __('In-Shop Operations') => '#',
      __('Credit Sales') => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.credit-sales.export-csv', request()->query()) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Export CSV') }}
      </a>
      <a href="{{ route('tenant.credit-sales.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> {{ __('New Credit Sale Entry') }}
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-journal-text fs-4"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Credit Sales') }}</span>
            <h5 class="fw-bold mb-0 text-dark">{{ $tenant->currency }} {{ number_format($totalCreditSales, 2) }}</h5>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-cash-stack fs-4"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Cash Collected on Spot') }}</span>
            <h5 class="fw-bold mb-0 text-success">{{ $tenant->currency }} {{ number_format($totalPaid, 2) }}</h5>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-danger-subtle text-danger p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-exclamation-octagon fs-4"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Net Khata Receivables') }}</span>
            <h5 class="fw-bold mb-0 text-danger">{{ $tenant->currency }} {{ number_format($totalOutstanding, 2) }}</h5>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="rounded-3 bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-receipt-cutoff fs-4"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Credit Bills') }}</span>
            <h5 class="fw-bold mb-0 text-dark">{{ number_format($totalInvoicesCount) }}</h5>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.credit-sales.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted mb-1">{{ __('Filter by Customer') }}</label>
          <select name="customer_id" class="form-select form-select-sm">
            <option value="">{{ __('All Customers') }}</option>
            @foreach($customers as $c)
              <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }} ({{ $c->phone ?? $c->code }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small fw-semibold text-muted mb-1">{{ __('Payment Status') }}</label>
          <select name="payment_status" class="form-select form-select-sm">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
            <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>{{ __('Partial') }}</option>
            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small fw-semibold text-muted mb-1">{{ __('Date From') }}</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small fw-semibold text-muted mb-1">{{ __('Date To') }}</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>

        <div class="col-12 col-md-3 d-flex gap-2">
          <div class="input-group input-group-sm flex-grow-1">
            <input type="text" name="search" class="form-control" placeholder="{{ __('Search invoice #, customer...') }}" value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
          </div>
          <a href="{{ route('tenant.credit-sales.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Invoices Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold text-dark">
        <i class="bi bi-list-ul text-primary me-2"></i>{{ __('Credit Customer Bills Log') }}
      </h6>
      <span class="text-muted small">{{ __('Showing ') }}{{ $invoices->firstItem() ?? 0 }} - {{ $invoices->lastItem() ?? 0 }} {{ __('of ') }}{{ $invoices->total() }}</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-muted text-uppercase">
            <th>{{ __('Bill #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Customer (Khata)') }}</th>
            <th>{{ __('Branch') }}</th>
            <th class="text-center">{{ __('Items') }}</th>
            <th class="text-end">{{ __('Bill Total') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due (Khata)') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end" style="min-width: 150px;">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td>
                <a href="{{ route('tenant.credit-sales.show', $inv) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                  {{ $inv->invoice_no }}
                </a>
              </td>
              <td>{{ $inv->date ? $inv->date->format('d-M-Y') : '-' }}</td>
              <td>
                <div class="fw-semibold text-dark">{{ $inv->customer?->name ?? 'Walk-in Credit' }}</div>
                @if($inv->customer?->phone)
                  <small class="text-muted font-monospace"><i class="bi bi-telephone me-1"></i>{{ $inv->customer->phone }}</small>
                @endif
              </td>
              <td><span class="badge bg-light text-dark border">{{ $inv->branch?->name ?? '-' }}</span></td>
              <td class="text-center">
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2">
                  {{ $inv->items->count() }} {{ __('items') }}
                </span>
              </td>
              <td class="text-end fw-bold font-monospace text-dark">
                {{ $tenant->currency }} {{ number_format($inv->total_amount, 2) }}
              </td>
              <td class="text-end font-monospace text-success">
                {{ $tenant->currency }} {{ number_format($inv->paid_amount, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold text-danger">
                {{ $tenant->currency }} {{ number_format($inv->due_amount, 2) }}
              </td>
              <td class="text-center">
                @if($inv->payment_status === 'paid')
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('Paid') }}
                  </span>
                @elseif($inv->payment_status === 'partial')
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                    <i class="bi bi-pie-chart-fill me-1"></i> {{ __('Partial') }}
                  </span>
                @else
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    <i class="bi bi-hourglass-split me-1"></i> {{ __('Unpaid') }}
                  </span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tenant.credit-sales.show', $inv) }}" class="btn btn-outline-primary" title="{{ __('View Invoice Details') }}">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('tenant.credit-sales.pdf', $inv) }}" class="btn btn-outline-danger" title="{{ __('Download PDF Bill') }}">
                    <i class="bi bi-file-pdf"></i>
                  </a>
                  <a href="{{ route('tenant.credit-sales.whatsapp', $inv) }}" target="_blank" class="btn btn-outline-success" title="{{ __('Share on WhatsApp') }}">
                    <i class="bi bi-whatsapp"></i>
                  </a>
                  <a href="{{ route('tenant.credit-sales.export-single-csv', $inv) }}" class="btn btn-outline-secondary" title="{{ __('Export Items CSV') }}">
                    <i class="bi bi-download"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5">
                <div class="py-4">
                  <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
                  <h6 class="fw-bold text-secondary">{{ __('No Credit Sales Recorded Yet') }}</h6>
                  <p class="text-muted small mb-3">{{ __('Start recording daily wholesale and hotel vegetable supplies for khata credit customers.') }}</p>
                  <a href="{{ route('tenant.credit-sales.create') }}" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('Record First Credit Sale') }}
                  </a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($invoices->hasPages())
      <div class="card-footer bg-white py-3 border-top">
        {{ $invoices->links() }}
      </div>
    @endif
  </div>
@endsection
