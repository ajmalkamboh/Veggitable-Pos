@extends('layouts.admin')

@section('title', __('Add Customer Account'))

@section('content')
  <x-breadcrumb 
    :title="__('Add Customer')" 
    :items="[
      __('Parties') => '#',
      __('Customers') => route('tenant.customers.index'),
      __('Create') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.customers.store') }}">
        @csrf

        <div class="row g-4">
          <!-- Main Customer Profile -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Customer / Business Information') }}</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer / Business Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Serena Hotel, Royal Gourmet Restaurant, Walk-in Cashier" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Name (Urdu)') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur') }}" placeholder="مثال: سرینا ہوٹل و ریسٹورنٹ">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Customer Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Category') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                      @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                    @error('type')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Custom Code -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Code / Account No.') }} <span class="text-muted">({{ __('Leave blank to auto-generate') }})</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. CUST-001">
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Contact Person -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Contact Person / Manager') }}</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" placeholder="e.g. Chef Tariq Mehmood">
                    @error('contact_person')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- NTN / Tax ID -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('NTN / CNIC / Tax ID') }}</label>
                    <input type="text" name="tax_id" class="form-control font-monospace @error('tax_id') is-invalid @enderror" value="{{ old('tax_id') }}" placeholder="e.g. 1234567-8">
                    @error('tax_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Phone & Mobile -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Telephone / Office') }}</label>
                    <input type="text" name="phone" class="form-control font-monospace @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="042-35876543">
                    @error('phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mobile / WhatsApp (for digital receipts)') }}</label>
                    <input type="text" name="mobile" class="form-control font-monospace @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="0321-9876543">
                    @error('mobile')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Address & City -->
                  <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">{{ __('Delivery Address') }}</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="e.g. 14-Gulberg III, Main Boulevard">
                    @error('address')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('City') }}</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" placeholder="e.g. Lahore">
                    @error('city')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Notes -->
                  <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Notes & Instructions') }}</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('Optional delivery instructions or billing preferences...') }}">{{ old('notes') }}</textarea>
                    @error('notes')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Credit Terms & Balances Column -->
          <div class="col-12 col-lg-4">
            <!-- Credit Terms Card -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Credit Limit & Ledger Terms') }}</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Maximum Credit Limit') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', '0.00') }}">
                  </div>
                  <small class="text-muted">{{ __('Set 0.00 for strictly cash-only counter sales.') }}</small>
                  @error('credit_limit')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Allowed Credit Days') }}</label>
                  <div class="input-group">
                    <input type="number" name="credit_days" class="form-control @error('credit_days') is-invalid @enderror" value="{{ old('credit_days', 0) }}" min="0">
                    <span class="input-group-text bg-light">{{ __('Days') }}</span>
                  </div>
                  <small class="text-muted">{{ __('Maximum payment window allowed for bills.') }}</small>
                  @error('credit_days')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Opening Balance') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', '0.00') }}">
                  </div>
                  <small class="text-muted">{{ __('Positive = Customer owes you (Receivable). Negative = Customer advance deposit.') }}</small>
                  @error('opening_balance')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Customer Account') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Save Customer Account') }}</button>
              <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

