@extends('layouts.admin')

@section('title', __('Add Supplier / Aarthi'))

@section('content')
  <x-breadcrumb 
    :title="__('Add Supplier')" 
    :items="[
      __('Parties') => '#',
      __('Suppliers') => route('tenant.suppliers.index'),
      __('Create') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.suppliers.store') }}">
        @csrf

        <div class="row g-4">
          <!-- Main Supplier Profile -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Business / Vendor Profile') }}</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier / Shop Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Al-Madina Commission Agent, Malik Fruit Farm" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier Name (Urdu)') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur') }}" placeholder="مثال: المدینہ آڑھت سبزی منڈی">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Supplier Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier Category') }} <span class="text-danger">*</span></label>
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
                    <label class="form-label fw-semibold">{{ __('Supplier Code') }} <span class="text-muted">({{ __('Leave blank to auto-generate') }})</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. AAR-001">
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Contact Person -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Contact Person / Aarthi Name') }}</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" placeholder="e.g. Haji Muhammad Aslam">
                    @error('contact_person')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Mandi Stall Number -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mandi Shop / Gala / Stall No.') }}</label>
                    <input type="text" name="mandi_stall_no" class="form-control @error('mandi_stall_no') is-invalid @enderror" value="{{ old('mandi_stall_no') }}" placeholder="e.g. Gala # 42, Block B, New Subzi Mandi">
                    @error('mandi_stall_no')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Phone & Mobile -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Office / Landline Phone') }}</label>
                    <input type="text" name="phone" class="form-control font-monospace @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="042-37654321">
                    @error('phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mobile / WhatsApp') }}</label>
                    <input type="text" name="mobile" class="form-control font-monospace @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="0300-1234567">
                    @error('mobile')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Address & City -->
                  <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">{{ __('Address') }}</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="e.g. Main Market, Subzi Mandi">
                    @error('address')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('City / Mandi Location') }}</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" placeholder="e.g. Lahore, Badami Bagh">
                    @error('city')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Notes -->
                  <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Notes & Terms') }}</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('Optional notes on special commission arrangements or grading...') }}">{{ old('notes') }}</textarea>
                    @error('notes')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial & Bank Details Column -->
          <div class="col-12 col-lg-4">
            <!-- Balances & Terms Card -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Financial Terms & Opening Balance') }}</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Commission Rate (%)') }}</label>
                  <div class="input-group">
                    <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control @error('commission_rate') is-invalid @enderror" value="{{ old('commission_rate', '5.00') }}">
                    <span class="input-group-text bg-light">%</span>
                  </div>
                  <small class="text-muted">{{ __('Standard Mandi commission percentage (e.g. 5% or 6%)') }}</small>
                  @error('commission_rate')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Opening Balance') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', '0.00') }}">
                  </div>
                  <small class="text-muted">{{ __('Positive = You owe supplier (Payable). Negative = Advance paid.') }}</small>
                  @error('opening_balance')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Payment Terms (Days Credit)') }}</label>
                  <div class="input-group">
                    <input type="number" name="payment_terms_days" class="form-control @error('payment_terms_days') is-invalid @enderror" value="{{ old('payment_terms_days', 7) }}" min="0">
                    <span class="input-group-text bg-light">{{ __('Days') }}</span>
                  </div>
                  @error('payment_terms_days')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Bank Account Card -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Bank Account Details') }}</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Bank Name') }}</label>
                  <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="e.g. HBL, Meezan Bank">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Account Title') }}</label>
                  <input type="text" name="bank_account_title" class="form-control" value="{{ old('bank_account_title') }}" placeholder="e.g. Haji Muhammad Aslam">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Account Number / IBAN') }}</label>
                  <input type="text" name="bank_account_number" class="form-control font-monospace" value="{{ old('bank_account_number') }}" placeholder="PK00MEZN0000000000000000">
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Supplier Account') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Save Supplier Profile') }}</button>
              <a href="{{ route('tenant.suppliers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

