@extends('layouts.admin')

@section('title', __('Day-End Register (Z-Report)'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Day-End Cash Register & Z-Report') }}</h4>
    <p class="text-muted small mb-0">{{ __('Daily till cash intake, mandi procurement payouts, petty expenses, and verified drawer closing reconciliation') }}</p>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="{{ route('tenant.reports.index') }}" class="btn btn-outline-secondary btn-sm me-2">
      <i class="bi bi-arrow-left me-1"></i> {{ __('Reports Hub') }}
    </a>
    <button onclick="window.print()" class="btn btn-secondary btn-sm">
      <i class="bi bi-printer me-1"></i> {{ __('Print Z-Report') }}
    </button>
  </div>
</div>

<!-- Date & Branch Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('tenant.reports.day-end') }}" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small text-muted mb-1">{{ __('Outlet / Branch') }}</label>
        <select name="branch_id" class="form-select form-select-sm">
          <option value="">{{ __('All Outlets / Consolidated') }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small text-muted mb-1">{{ __('Register Business Date') }}</label>
        <input type="date" name="date" value="{{ $date }}" class="form-control form-select-sm">
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-info text-white btn-sm w-100">
          <i class="bi bi-calendar-check me-1"></i> {{ __('Fetch Day-End Summary') }}
        </button>
        <a href="{{ route('tenant.reports.day-end') }}" class="btn btn-light btn-sm border">
          <i class="bi bi-arrow-counterclockwise"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- Daily Cash Reconciliation Metrics -->
<div class="row g-3 mb-4">
  <!-- 1. Cash Inflow -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
      <span class="text-muted small">{{ __('Total Cash Inflow (Sales)') }}</span>
      <h3 class="fw-bold text-success mt-2 mb-0">+{{ number_format($reportData['sales_cash_in'], 2) }}</h3>
      <small class="text-muted">{{ __('Counter collections & credit recoveries') }}</small>
    </div>
  </div>

  <!-- 2. Cash Outflow Mandi -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
      <span class="text-muted small">{{ __('Mandi Cash Paid Out') }}</span>
      <h3 class="fw-bold text-primary mt-2 mb-0">-{{ number_format($reportData['purchase_cash_out'], 2) }}</h3>
      <small class="text-muted">{{ __('Produce lot & commission settlements') }}</small>
    </div>
  </div>

  <!-- 3. Cash Outflow Expenses -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
      <span class="text-muted small">{{ __('Operational Expenses') }}</span>
      <h3 class="fw-bold text-warning mt-2 mb-0">-{{ number_format($reportData['expense_cash_out'], 2) }}</h3>
      <small class="text-muted">{{ __('Fuel, ice, staff meals, mandi tea, freight') }}</small>
    </div>
  </div>

  <!-- 4. Net Drawer Cash Flow -->
  <div class="col-md-3">
    <div class="card border-0 shadow-sm p-3 border-start border-{{ $reportData['net_cash_flow'] >= 0 ? 'success' : 'danger' }} border-4">
      <span class="text-muted small">{{ __('Net Daily Cash Flow') }}</span>
      <h3 class="fw-bold text-{{ $reportData['net_cash_flow'] >= 0 ? 'success' : 'danger' }} mt-2 mb-0">
        {{ $reportData['net_cash_flow'] >= 0 ? '+' : '' }}{{ number_format($reportData['net_cash_flow'], 2) }}
      </h3>
      <small class="text-muted">{{ __('Surplus / Deficit to Till Safe') }}</small>
    </div>
  </div>
</div>

<!-- Detailed Breakdown Card -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0 text-dark">
      <i class="bi bi-file-earmark-spreadsheet me-2 text-info"></i>
      {{ __('Z-Report Audit Ledger for ') }} <span class="text-primary">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span>
    </h6>
  </div>
  <div class="card-body p-4">
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Cash Flow Movement') }}</th>
            <th>{{ __('Account / Category') }}</th>
            <th class="text-end">{{ __('Inflow (+)') }}</th>
            <th class="text-end">{{ __('Outflow (-)') }}</th>
            <th class="text-end">{{ __('Net Impact') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="fw-semibold text-success">
              <i class="bi bi-arrow-down-left-circle me-1"></i> {{ __('POS Register Inflow') }}
            </td>
            <td>{{ __('Retail & Wholesale Vegetable Sales') }}</td>
            <td class="text-end text-success fw-bold">{{ number_format($reportData['sales_cash_in'], 2) }}</td>
            <td class="text-end text-muted">-</td>
            <td class="text-end text-success fw-bold">+{{ number_format($reportData['sales_cash_in'], 2) }}</td>
          </tr>
          <tr>
            <td class="fw-semibold text-primary">
              <i class="bi bi-arrow-up-right-circle me-1"></i> {{ __('Mandi Procurement Outflow') }}
            </td>
            <td>{{ __('Supplier Invoices & Mandi Commission Payments') }}</td>
            <td class="text-end text-muted">-</td>
            <td class="text-end text-primary fw-bold">{{ number_format($reportData['purchase_cash_out'], 2) }}</td>
            <td class="text-end text-primary fw-bold">-{{ number_format($reportData['purchase_cash_out'], 2) }}</td>
          </tr>
          <tr>
            <td class="fw-semibold text-warning">
              <i class="bi bi-arrow-up-right-circle me-1"></i> {{ __('Petty Operating Expenses') }}
            </td>
            <td>{{ __('Daily Shop Operations, Utilities & Consumables') }}</td>
            <td class="text-end text-muted">-</td>
            <td class="text-end text-warning fw-bold">{{ number_format($reportData['expense_cash_out'], 2) }}</td>
            <td class="text-end text-warning fw-bold">-{{ number_format($reportData['expense_cash_out'], 2) }}</td>
          </tr>
        </tbody>
        <tfoot class="table-light">
          <tr class="fw-bold fs-6">
            <td colspan="4" class="text-end">{{ __('Net Daily Cash Movement:') }}</td>
            <td class="text-end text-{{ $reportData['net_cash_flow'] >= 0 ? 'success' : 'danger' }}">
              {{ $reportData['net_cash_flow'] >= 0 ? '+' : '' }}{{ number_format($reportData['net_cash_flow'], 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="row mt-4 pt-3 border-top">
      <div class="col-md-6">
        <p class="text-muted small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          {{ __('The Z-Report resets the daily trading session register. Ensure actual cash counted in the drawer matches the net cash balance before safe deposit.') }}
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="{{ route('tenant.pos.counter') }}" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-clock-history me-1"></i> {{ __('Open POS Counter') }}
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
