@extends('layouts.admin')

@section('title', __('Mandi Procurement & Purchases Report'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Mandi Procurement & Purchase Report') }}</h4>
    <p class="text-muted small mb-0">{{ __('Audit of vegetable & fruit wholesale lots, commission agent deductions, mandi fees, and supplier payables') }}</p>
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
    <form method="GET" action="{{ route('tenant.reports.purchases') }}" class="row g-2 align-items-end">
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
        <label class="form-label small text-muted mb-1">{{ __('Mandi Commission Agent / Supplier') }}</label>
        <select name="supplier_id" class="form-select form-select-sm">
          <option value="">{{ __('All Suppliers') }}</option>
          @foreach($suppliers as $s)
            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
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
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-funnel me-1"></i> {{ __('Filter') }}
        </button>
        <a href="{{ route('tenant.reports.purchases') }}" class="btn btn-light btn-sm border">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Purchase Metric Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Produce Net Value') }}</span>
      <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($reportData['subtotal'], 2) }}</h4>
      <small class="text-muted">{{ __('Base Produce Lot Price') }}</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Mandi Surcharges') }}</span>
      <h4 class="fw-bold text-warning mt-1 mb-0">{{ number_format($reportData['commission'] + $reportData['mandi_fee'] + $reportData['freight'], 2) }}</h4>
      <small class="text-muted">{{ __('Comm: ') }}{{ number_format($reportData['commission'], 2) }} | {{ __('Fee: ') }}{{ number_format($reportData['mandi_fee'], 2) }}</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Total Procurement Bill') }}</span>
      <h4 class="fw-bold text-primary mt-1 mb-0">{{ number_format($reportData['total_amount'], 2) }}</h4>
      <small class="text-muted">{{ __('Produce + All Mandi Charges') }}</small>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <span class="text-muted small">{{ __('Paid vs Outstanding Due') }}</span>
      <h5 class="fw-bold text-dark mt-1 mb-0">
        <span class="text-success">{{ number_format($reportData['paid'], 2) }}</span> / 
        <span class="text-danger">{{ number_format($reportData['due'], 2) }}</span>
      </h5>
      <small class="text-muted">{{ __('Supplier Clearing Balance') }}</small>
    </div>
  </div>
</div>

<!-- Purchases Invoices Table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0 text-dark">{{ __('Mandi Procurement Invoices') }} ({{ count($reportData['purchases']) }})</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Invoice #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Supplier / Commission Agent') }}</th>
            <th>{{ __('Outlet') }}</th>
            <th class="text-end">{{ __('Produce Subtotal') }}</th>
            <th class="text-end">{{ __('Commission') }}</th>
            <th class="text-end">{{ __('Mandi Fee') }}</th>
            <th class="text-end">{{ __('Freight') }}</th>
            <th class="text-end">{{ __('Total Bill') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-end">{{ __('Due') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reportData['purchases'] as $p)
            <tr>
              <td>
                <span class="fw-bold font-monospace text-dark">{{ $p->invoice_no }}</span>
              </td>
              <td>{{ $p->date ? \Carbon\Carbon::parse($p->date)->format('d M Y') : '-' }}</td>
              <td>
                <span class="fw-semibold text-dark">{{ $p->supplier->name ?? '-' }}</span>
              </td>
              <td>{{ $p->branch->name ?? '-' }}</td>
              <td class="text-end">{{ number_format($p->subtotal, 2) }}</td>
              <td class="text-end text-muted">{{ number_format($p->commission_amount, 2) }}</td>
              <td class="text-end text-muted">{{ number_format($p->mandi_fee, 2) }}</td>
              <td class="text-end text-muted">{{ number_format($p->freight_charges, 2) }}</td>
              <td class="text-end fw-bold text-primary">{{ number_format($p->total_amount, 2) }}</td>
              <td class="text-end text-success">{{ number_format($p->paid_amount, 2) }}</td>
              <td class="text-end text-danger fw-semibold">{{ number_format($p->due_amount, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-4">
                {{ __('No procurement invoices found matching selected criteria.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
