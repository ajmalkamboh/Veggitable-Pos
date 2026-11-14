@extends('layouts.admin')

@section('title', 'Business Onboarding Wizard')

@section('content')
  <x-breadcrumb 
    title="Business Onboarding & Setup" 
    :items="[
      'Business Setup' => '#',
      'Checklist' => '#'
    ]"
  />

  @if(session('warning'))
    <div class="alert alert-warning py-3 px-4 shadow-sm mb-4">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
        <div>
          <h6 class="mb-0 fw-bold">Initial Configuration Required</h6>
          <small>{{ session('warning') }}</small>
        </div>
      </div>
    </div>
  @endif

  <!-- Overall Progress Bar Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h5 class="fw-bold mb-1">{{ $tenant->name }} Setup Checklist</h5>
          <p class="text-muted small mb-0">Complete the 4 foundation steps to activate POS billing and inventory management.</p>
        </div>
        <div class="text-end">
          <span class="fs-4 fw-bold text-primary">{{ $progress }}%</span>
          <span class="d-block small text-muted">Completed</span>
        </div>
      </div>
      <div class="progress" style="height: 12px;">
        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $progress }}%"></div>
      </div>
    </div>
  </div>

  <!-- Checklist Grid -->
  <div class="row g-4 mb-4">
    <!-- Step 1: Business Profile -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                <i class="bi bi-buildings fs-4"></i>
              </div>
              @if(!empty($tenant->address) && !empty($tenant->phone))
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-check-circle-fill me-1"></i> Completed
                </span>
              @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
              @endif
            </div>
            <h6 class="fw-bold mb-1">1. Business Profile & Identity</h6>
            <p class="text-muted small mb-0">Business address, phone, logo, currency ({{ $tenant->currency }}), fiscal year start, and opening date.</p>
          </div>
          <div class="mt-4 pt-3 border-top">
            <a href="{{ route('tenant.onboarding.step1') }}" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-pencil me-1"></i> {{ !empty($tenant->address) ? 'Review / Edit Profile' : 'Configure Profile' }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 2: Branch Setup -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="bg-success-subtle text-success p-3 rounded-circle">
                <i class="bi bi-shop fs-4"></i>
              </div>
              @if($headquarters)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-check-circle-fill me-1"></i> Configured
                </span>
              @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
              @endif
            </div>
            <h6 class="fw-bold mb-1">2. Primary Shop Outlet / Branch</h6>
            <p class="text-muted small mb-0">Main retail shop or Mandi auction stall: {{ $headquarters?->name ?? 'Not set' }} ({{ $headquarters?->code ?? 'HQ-01' }}).</p>
          </div>
          <div class="mt-4 pt-3 border-top">
            <a href="{{ route('tenant.onboarding.step2') }}" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-geo-alt me-1"></i> {{ $headquarters ? 'Review Branch Details' : 'Configure Branch' }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 3: Cash & Bank Accounts -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="bg-info-subtle text-info p-3 rounded-circle">
                <i class="bi bi-cash-coin fs-4"></i>
              </div>
              @if($accountsCount > 0)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-check-circle-fill me-1"></i> {{ $accountsCount }} Account(s)
                </span>
              @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
              @endif
            </div>
            <h6 class="fw-bold mb-1">3. Cash Drawer & Bank Accounts</h6>
            <p class="text-muted small mb-0">Counter cash register drawer, petty cash, and primary commercial bank account with opening balances.</p>
          </div>
          <div class="mt-4 pt-3 border-top">
            <a href="{{ route('tenant.onboarding.step3') }}" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-wallet2 me-1"></i> {{ $accountsCount > 0 ? 'Review Accounts' : 'Setup Accounts' }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 4: Units & Categories -->
    <div class="col-12 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                <i class="bi bi-tags fs-4"></i>
              </div>
              @if($unitsCount > 0 && $categoriesCount > 0)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                  <i class="bi bi-check-circle-fill me-1"></i> Ready
                </span>
              @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
              @endif
            </div>
            <h6 class="fw-bold mb-1">4. Master Units & Categories</h6>
            <p class="text-muted small mb-0">Pre-configured vegetable units (kg, g, bori, crate) and Mandi expense/product classifications.</p>
          </div>
          <div class="mt-4 pt-3 border-top">
            <a href="{{ route('tenant.onboarding.step4') }}" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-check2-all me-1"></i> Review & Complete Setup
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($tenant->isOnboarded())
    <div class="alert alert-success d-flex align-items-center justify-content-between py-3 px-4 shadow-sm">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-4 text-success"></i>
        <div>
          <h6 class="mb-0 fw-bold">Business Setup is 100% Complete</h6>
          <small>You can edit your configuration at any time without impacting past invoices or transactions.</small>
        </div>
      </div>
      <a href="{{ route('dashboard') }}" class="btn btn-success btn-sm px-3">Go to POS / Dashboard</a>
    </div>
  @else
    <div class="text-center py-3">
      <a href="{{ route('tenant.onboarding.step1') }}" class="btn btn-primary px-4 py-2 fs-6 fw-semibold">
        <span>Proceed to Step 1: Business Profile</span>
        <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  @endif
@endsection
