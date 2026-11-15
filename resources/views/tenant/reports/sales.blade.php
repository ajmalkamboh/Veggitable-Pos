@extends('layouts.admin')

@section('title', __('Sales Analytics Report'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Sales Analytics & Turnover Report') }}</h4>
    <p class="text-muted small mb-0">{{ __('Detailed breakdown of retail and wholesale vegetable sales, profit margins, and collections') }}</p>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="{{ route('tenant.reports.index') }}" class="btn btn-outline-secondary btn-sm me-2">
      <i class="bi bi-arrow-left me-1"></i> {{ __('Reports Hub') }}
    </a>
    <button onclick="window.print()" class="btn btn-secondary btn-sm">
      <i class="bi bi-printer me-1"></i> {{ __('Print Report') }}
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('tenant.reports.sales') }}" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">{{ __('Outlet / Branch') }}</label>
        <select name="branch_id" class="form-select form-select-sm">
          <option value="">{{ __('All Outlets') }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">{{ __('Customer') }}</label>
        <select name="customer_id" class="form-select form-select-sm">
          <option value="">{{ __('All Customers') }}</option>
          @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->phone }})</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">{{ __('From Date') }}</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-select-sm">
      </div>

      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">{{ __('To Date') }}</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-select-sm">
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm w-100">
          <i class="bi bi-funnel me-1"></i> {{ __('Filter') }}
        </button>
        <a href="{{ route('tenant.reports.sales') }}" class="btn btn-light btn-sm border">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Sales Metric Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Gross Revenue') }}</span>
      <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($reportData['gross_sales'], 2) }}</h4>
      <small class="text-muted">{{ __('Total Item Sales Value') }}</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Net Turnover') }}</span>
      <h4 class="fw-bold text-success mt-1 mb-0">{{ number_format($reportData['net_sales'], 2) }}</h4>
      <small class="text-muted">{{ __('Less Discounts: ') }} {{ number_format($reportData['discounts'], 2) }}</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Gross Profit') }}</span>
      <h4 class="fw-bold text-primary mt-1 mb-0">{{ number_format($reportData['gross_profit'], 2) }}</h4>
      <small class="text-muted">{{ __('Margin: ') }} <span class="badge bg-primary-subtle text-primary">{{ $reportData['margin_pct'] }}%</span></small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Collections & Receivables') }}</span>
      <h5 class="fw-bold text-dark mt-1 mb-0">
        <span class="text-success">{{ number_format($reportData['paid'], 2) }}</span> / 
        <span class="text-danger">{{ number_format($reportData['due'], 2) }}</span>
      </h5>
      <small class="text-muted">{{ __('Paid vs Outstanding Due') }}</small>
    </div>
  </div>
</div>

<!-- Sales Invoices Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0 text-dark">{{ __('Sales Invoices & Transactions') }} ({{ count($reportData['invoices']) }})</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Invoice #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Outlet') }}</th>
            <th class="text-end">{{ __('Subtotal') }}</th>
            <th class="text-end">{{ __('Discount') }}</th>
            <th class="text-end">{{ __('Net Total') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due') }}</th>
            <th class="text-end">{{ __('Profit') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reportData['invoices'] as $inv)
            <tr>
              <td>
                <span class="fw-bold font-monospace text-dark">{{ $inv->invoice_no }}</span>
              </td>
              <td>{{ $inv->date ? \Carbon\Carbon::parse($inv->date)->format('d M Y') : '-' }}</td>
              <td>
                @if($inv->customer)
                  <span class="fw-semibold">{{ $inv->customer->name }}</span>
                @else
                  <span class="text-muted fst-italic">{{ __('Walk-in Customer') }}</span>
                @endif
              </td>
              <td>{{ $inv->branch->name ?? '-' }}</td>
              <td class="text-end">{{ number_format($inv->subtotal, 2) }}</td>
              <td class="text-end text-danger">{{ number_format($inv->discount_amount, 2) }}</td>
              <td class="text-end fw-bold">{{ number_format($inv->total_amount, 2) }}</td>
              <td class="text-end text-success">{{ number_format($inv->paid_amount, 2) }}</td>
              <td class="text-end text-danger">{{ number_format($inv->due_amount, 2) }}</td>
              <td class="text-end text-primary fw-semibold">{{ number_format($inv->profit_amount, 2) }}</td>
              <td class="text-center">
                @if($inv->status === 'paid')
                  <span class="badge bg-success-subtle text-success">{{ __('Paid') }}</span>
                @elseif($inv->status === 'partial')
                  <span class="badge bg-warning-subtle text-warning">{{ __('Partial') }}</span>
                @elseif($inv->status === 'due')
                  <span class="badge bg-danger-subtle text-danger">{{ __('Due') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($inv->status) }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-4">
                {{ __('No sales transactions found matching selected criteria.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
