@extends('layouts.admin')

@section('title', __('Sales Invoices & Orders Log'))

@section('content')
  <x-breadcrumb 
    :title="__('Sales & Invoices')" 
    :items="[
      __('POS & Sales') => route('tenant.sales.index'),
      __('Invoices') => route('tenant.sales.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.pos.counter') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart4"></i>
        <span>{{ __('Open POS Counter') }}</span>
      </a>
      <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-people"></i>
        <span>{{ __('Customer Accounts') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded p-3 fs-3">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Invoices') }}</span>
            <h4 class="mb-0 fw-bold font-monospace">{{ number_format($totalInvoicesCount) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success rounded p-3 fs-3">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Sales Revenue') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-success">{{ $tenant->currency }} {{ number_format($totalSales, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-info-subtle text-info rounded p-3 fs-3">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Cash Collected') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-info">{{ $tenant->currency }} {{ number_format($totalPaid, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-danger-subtle text-danger rounded p-3 fs-3">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Credit Receivables') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-danger">{{ $tenant->currency }} {{ number_format($totalReceivable, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.sales.index') }}" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Branch Outlet') }}</label>
          <select name="branch_id" class="form-select form-select-sm">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small fw-semibold text-muted">{{ __('Payment Status') }}</label>
          <select name="payment_status" class="form-select form-select-sm">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
            <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>{{ __('Partial') }}</option>
            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small fw-semibold text-muted">{{ __('Payment Method') }}</label>
          <select name="payment_method" class="form-select form-select-sm">
            <option value="">{{ __('All Methods') }}</option>
            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>{{ __('Cash') }}</option>
            <option value="credit" {{ request('payment_method') == 'credit' ? 'selected' : '' }}>{{ __('Credit (Khata)') }}</option>
            <option value="bank_card" {{ request('payment_method') == 'bank_card' ? 'selected' : '' }}>{{ __('Bank Card') }}</option>
            <option value="qr_transfer" {{ request('payment_method') == 'qr_transfer' ? 'selected' : '' }}>{{ __('QR / Online') }}</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label small fw-semibold text-muted">{{ __('Search Invoice or Customer') }}</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Invoice #, Customer name/phone...') }}" value="{{ request('search') }}">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Invoices Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-receipt me-2 text-primary"></i>{{ __('Sales History Log') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Invoice #') }}</th>
            <th>{{ __('Date & Time') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Method') }}</th>
            <th class="text-end">{{ __('Total') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.sales.show', $inv) }}" class="text-decoration-none text-primary">
                  {{ $inv->invoice_no }}
                </a>
              </td>
              <td class="small text-muted">{{ $inv->created_at->format('d M, Y H:i') }}</td>
              <td>
                @if($inv->customer)
                  <a href="{{ route('tenant.customers.show', $inv->customer) }}" class="text-decoration-none text-dark fw-semibold">
                    {{ $inv->customer->name }}
                  </a>
                @else
                  <span class="text-muted small">{{ __('Walk-in Customer') }}</span>
                @endif
              </td>
              <td><span class="small">{{ $inv->branch->name ?? '-' }}</span></td>
              <td><span class="badge bg-light text-dark border">{{ ucfirst($inv->payment_method) }}</span></td>
              <td class="text-end font-monospace fw-bold text-dark">
                {{ $tenant->currency }} {{ number_format($inv->total_amount, 2) }}
              </td>
              <td class="text-end font-monospace text-success">
                {{ $tenant->currency }} {{ number_format($inv->paid_amount, 2) }}
              </td>
              <td class="text-end font-monospace {{ $inv->due_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                {{ $tenant->currency }} {{ number_format($inv->due_amount, 2) }}
              </td>
              <td class="text-center">
                @if($inv->payment_status === 'paid')
                  <span class="badge bg-success">{{ __('Paid') }}</span>
                @elseif($inv->payment_status === 'partial')
                  <span class="badge bg-warning text-dark">{{ __('Partial') }}</span>
                @else
                  <span class="badge bg-danger">{{ __('Unpaid') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tenant.pos.receipt', $inv) }}" target="_blank" class="btn btn-outline-secondary" title="{{ __('Print Thermal Receipt') }}">
                    <i class="bi bi-printer"></i>
                  </a>
                  <a href="{{ route('tenant.sales.show', $inv) }}" class="btn btn-outline-primary" title="{{ __('View Details') }}">
                    <i class="bi bi-eye"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                {{ __('No sales invoices found matching criteria.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($invoices->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $invoices->links() }}
      </div>
    @endif
  </div>
@endsection

