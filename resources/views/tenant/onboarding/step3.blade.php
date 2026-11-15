@extends('layouts.admin')

@section('title', 'Onboarding Step 3: Cash & Bank Accounts')

@section('content')
  <x-breadcrumb 
    title="Step 3: Cash & Bank Accounts" 
    :items="[
      'Business Setup' => route('tenant.onboarding.index'),
      'Step 3: Accounts' => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">3. Financial Accounts & Opening Balances</h6>
            <span class="badge bg-primary">Step 3 of 4</span>
          </div>
          <small class="text-muted">Set up your physical cash till/drawer and optional shop commercial bank account.</small>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('tenant.onboarding.step3.save') }}">
            @csrf

            <!-- Section 1: Main Cash Counter Drawer -->
            <div class="border rounded p-3 mb-4 bg-light">
              <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="bi bi-cash-stack text-success fs-5"></i>
                <h6 class="fw-bold mb-0 text-dark">Primary Cash Drawer / Till</h6>
              </div>

              <div class="row g-3">
                <div class="col-md-7">
                  <label for="cash_drawer_name" class="form-label fw-semibold">Cash Drawer Name <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    name="cash_drawer_name" 
                    id="cash_drawer_name" 
                    class="form-control @error('cash_drawer_name') is-invalid @enderror" 
                    value="{{ old('cash_drawer_name', $cashAccount?->name ?? 'Main Cash Counter Drawer') }}" 
                    required
                  >
                  @error('cash_drawer_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-5">
                  <label for="cash_opening_balance" class="form-label fw-semibold">Opening Float Balance (Rs.) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text">{{ $tenant->currency }}</span>
                    <input 
                      type="number" 
                      step="0.01" 
                      name="cash_opening_balance" 
                      id="cash_opening_balance" 
                      class="form-control @error('cash_opening_balance') is-invalid @enderror" 
                      value="{{ old('cash_opening_balance', $cashAccount?->opening_balance ?? '0.00') }}" 
                      required
                    >
                  </div>
                  @error('cash_opening_balance')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <div class="form-text">Cash on hand in drawer at system start.</div>
                </div>
              </div>
            </div>

            <!-- Section 2: Shop Bank Account (Optional) -->
            <div class="border rounded p-3 mb-4">
              <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <i class="bi bi-bank text-primary fs-5"></i>
                <h6 class="fw-bold mb-0 text-dark">Shop Commercial Bank Account (Optional)</h6>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="bank_name" class="form-label fw-semibold">Bank Name</label>
                  <input 
                    type="text" 
                    name="bank_name" 
                    id="bank_name" 
                    class="form-control @error('bank_name') is-invalid @enderror" 
                    value="{{ old('bank_name', $bankAccount?->bank_name ?? 'Habib Bank Limited (HBL)') }}" 
                    placeholder="e.g. Meezan Bank, HBL, MCB"
                  >
                  @error('bank_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-6">
                  <label for="bank_account_name" class="form-label fw-semibold">Account Title</label>
                  <input 
                    type="text" 
                    name="bank_account_name" 
                    id="bank_account_name" 
                    class="form-control @error('bank_account_name') is-invalid @enderror" 
                    value="{{ old('bank_account_name', $bankAccount?->name ?? 'Shop Business Account') }}" 
                    placeholder="e.g. Al-Madina Fresh Grocers"
                  >
                  @error('bank_account_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-7">
                  <label for="account_number" class="form-label fw-semibold">Account / IBAN Number</label>
                  <input 
                    type="text" 
                    name="account_number" 
                    id="account_number" 
                    class="form-control font-monospace @error('account_number') is-invalid @enderror" 
                    value="{{ old('account_number', $bankAccount?->account_number ?? 'PK00MEZN00012345678901') }}" 
                    placeholder="e.g. PK00HABB00012345678901"
                  >
                  @error('account_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-5">
                  <label for="bank_opening_balance" class="form-label fw-semibold">Opening Balance (Rs.)</label>
                  <div class="input-group">
                    <span class="input-group-text">{{ $tenant->currency }}</span>
                    <input 
                      type="number" 
                      step="0.01" 
                      name="bank_opening_balance" 
                      id="bank_opening_balance" 
                      class="form-control @error('bank_opening_balance') is-invalid @enderror" 
                      value="{{ old('bank_opening_balance', $bankAccount?->opening_balance ?? '0.00') }}"
                    >
                  </div>
                  @error('bank_opening_balance')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <a href="{{ route('tenant.onboarding.step2') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Step 2
              </a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <span>Save Accounts & Continue to Step 4</span>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

