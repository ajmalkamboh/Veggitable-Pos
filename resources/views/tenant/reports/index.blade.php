@extends('layouts.admin')

@section('title', __('Business Reports Hub'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Reports & Business Intelligence Hub') }}</h4>
    <p class="text-muted small mb-0">{{ __('Comprehensive audit, sales analytics, mandi procurement, and financial statements') }}</p>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-speedometer2 me-1"></i> {{ __('Back to Dashboard') }}
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- 1. Sales & Revenue Analytics -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm hover-shadow transition">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-success-subtle text-success p-3 fs-4">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('Sales Analytics') }}</h5>
            <span class="badge bg-success-subtle text-success">{{ __('POS & Invoices') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Detailed turnover, cash vs credit sales, item discounts, tax audits, and gross margin profit tracking across branches.') }}
        </p>
        <a href="{{ route('tenant.reports.sales') }}" class="btn btn-outline-success btn-sm w-100">
          {{ __('Open Sales Report') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- 2. Mandi Procurement & Purchases -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-4">
            <i class="bi bi-truck"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('Mandi Procurement') }}</h5>
            <span class="badge bg-primary-subtle text-primary">{{ __('Vegetables & Fruits') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Track supplier lot deliveries, mandi commission fees, market committee charges, bardana bag deductions, and payables.') }}
        </p>
        <a href="{{ route('tenant.reports.purchases') }}" class="btn btn-outline-primary btn-sm w-100">
          {{ __('Open Procurement Report') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- 3. Daily Register Z-Report -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-info-subtle text-info p-3 fs-4">
            <i class="bi bi-calculator"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('Day-End Register (Z-Report)') }}</h5>
            <span class="badge bg-info-subtle text-info">{{ __('Daily Cash Summary') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Till reconciliation, daily cash intake, mandi cash payouts, operational petty expenses, and net daily cash flow.') }}
        </p>
        <a href="{{ route('tenant.reports.day-end') }}" class="btn btn-outline-info btn-sm w-100">
          {{ __('View Daily Z-Report') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- 4. Profit & Loss Statement -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-warning-subtle text-warning p-3 fs-4">
            <i class="bi bi-journal-text"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('Profit & Loss (Income)') }}</h5>
            <span class="badge bg-warning-subtle text-warning">{{ __('Double-Entry') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Financial income statement showing gross produce revenue, cost of goods sold (COGS), operating expenses, and net profit.') }}
        </p>
        <a href="{{ route('tenant.accounting.pnl') }}" class="btn btn-outline-warning btn-sm w-100">
          {{ __('Generate P&L Statement') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- 5. Balance Sheet -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-danger-subtle text-danger p-3 fs-4">
            <i class="bi bi-bank"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('Balance Sheet') }}</h5>
            <span class="badge bg-danger-subtle text-danger">{{ __('Assets & Liabilities') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Snapshot of financial solvency: cash reserves, inventory stock valuation, trade receivables vs mandi trade payables & equity.') }}
        </p>
        <a href="{{ route('tenant.accounting.balance-sheet') }}" class="btn btn-outline-danger btn-sm w-100">
          {{ __('View Balance Sheet') }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- 6. General Ledger & Trial Balance -->
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle bg-secondary-subtle text-secondary p-3 fs-4">
            <i class="bi bi-book"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1 text-dark">{{ __('General Ledger & Trial Balance') }}</h5>
            <span class="badge bg-secondary-subtle text-secondary">{{ __('Audit Logs') }}</span>
          </div>
        </div>
        <p class="text-muted small mb-4">
          {{ __('Account-by-account debit and credit entries, transaction references, running balances, and verified trial balance proof.') }}
        </p>
        <div class="d-flex gap-2">
          <a href="{{ route('tenant.accounting.ledger') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">
            {{ __('General Ledger') }}
          </a>
          <a href="{{ route('tenant.accounting.trial-balance') }}" class="btn btn-outline-dark btn-sm flex-grow-1">
            {{ __('Trial Balance') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
