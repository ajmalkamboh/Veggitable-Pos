@extends('layouts.admin')

@section('title', __('Add Chart of Account'))

@section('content')
  <x-breadcrumb 
    :title="__('Add New Account')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Chart of Accounts') => route('tenant.accounting.coa.index'),
      __('Add Account') => route('tenant.accounting.coa.create')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.coa.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Cancel') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Please correct the following errors:') }}</div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-plus-circle me-2 text-primary"></i>{{ __('Create General Ledger Account') }}
          </h6>
        </div>
        <form method="POST" action="{{ route('tenant.accounting.coa.store') }}" class="card-body p-4">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold required">{{ __('Account Code') }}</label>
              <input type="text" name="account_code" class="form-control font-monospace @error('account_code') is-invalid @enderror" placeholder="e.g. 1060, 2030, 5080" value="{{ old('account_code') }}" required>
              <small class="text-muted fs-11">{{ __('Unique code in chart of accounts.') }}</small>
              @error('account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold required">{{ __('Account Classification (Type)') }}</label>
              <select name="type" id="accountType" class="form-select @error('type') is-invalid @enderror" required>
                <option value="">{{ __('Select Classification...') }}</option>
                <option value="asset" {{ old('type') === 'asset' ? 'selected' : '' }}>{{ __('Asset (اثاثہ)') }}</option>
                <option value="liability" {{ old('type') === 'liability' ? 'selected' : '' }}>{{ __('Liability (واجبات)') }}</option>
                <option value="equity" {{ old('type') === 'equity' ? 'selected' : '' }}>{{ __('Equity & Capital (سرمایہ)') }}</option>
                <option value="revenue" {{ old('type') === 'revenue' ? 'selected' : '' }}>{{ __('Revenue / Sales (آمدنی)') }}</option>
                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>{{ __('Expense / COGS (اخراجات)') }}</option>
              </select>
              @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold required">{{ __('Account Name (English)') }}</label>
              <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Delivery Motorcycle, Cold Storage Mach..." value="{{ old('name') }}" required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Account Name (Urdu - اردو نام)') }}</label>
              <input type="text" name="name_ur" class="form-control text-end" placeholder="مثلاً ڈلیوری موٹر سائیکل، گودام فکسچرز" value="{{ old('name_ur') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold required">{{ __('Subtype') }}</label>
              <select name="subtype" class="form-select" required>
                <option value="current_asset">{{ __('Current Asset (جاری اثاثہ)') }}</option>
                <option value="non_current_asset">{{ __('Non-Current / Fixed Asset (مستقل اثاثہ)') }}</option>
                <option value="current_liability">{{ __('Current Liability (جاری واجبات)') }}</option>
                <option value="non_current_liability">{{ __('Long-term Liability (طویل المدتی واجبات)') }}</option>
                <option value="equity">{{ __('Equity / Capital (سرمایہ)') }}</option>
                <option value="operating_revenue">{{ __('Operating Revenue (کاروباری آمدنی)') }}</option>
                <option value="direct_expense">{{ __('Direct Cost / COGS (براہ راست لاگت)') }}</option>
                <option value="operating_expense">{{ __('Operating Expense (انتظامی و دفتری اخراجات)') }}</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('Opening Balance (PKR)') }}</label>
              <input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="0.00" value="{{ old('opening_balance', '0.00') }}">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">{{ __('Description & Notes') }}</label>
              <textarea name="description" rows="2" class="form-control" placeholder="{{ __('Optional notes regarding account purpose...') }}">{{ old('description') }}</textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="{{ route('tenant.accounting.coa.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
            <button type="submit" class="btn btn-primary px-4 fw-bold">
              <i class="bi bi-check2-circle me-1"></i> {{ __('Create Account') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

