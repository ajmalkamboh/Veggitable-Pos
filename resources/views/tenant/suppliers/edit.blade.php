@extends('layouts.admin')

@section('title', __('Edit Supplier'))

@section('content')
  <x-breadcrumb 
    :title="__('Edit Supplier')" 
    :items="[
      __('Parties') => '#',
      __('Suppliers') => route('tenant.suppliers.index'),
      $supplier->name => route('tenant.suppliers.show', $supplier),
      __('Edit') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.suppliers.update', $supplier) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <!-- Main Supplier Profile -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">{{ __('Business / Vendor Profile') }}</h6>
                <span class="badge bg-light text-dark font-monospace">{{ $supplier->code }}</span>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier / Shop Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name) }}" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier Name (Urdu)') }}</label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur', $supplier->name_ur) }}">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Supplier Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier Category') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                      @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type', $supplier->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                    @error('type')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Custom Code -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Supplier Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code', $supplier->code) }}" required>
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Contact Person -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Contact Person / Aarthi Name') }}</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person', $supplier->contact_person) }}">
                    @error('contact_person')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Mandi Stall Number -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mandi Shop / Gala / Stall No.') }}</label>
                    <input type="text" name="mandi_stall_no" class="form-control @error('mandi_stall_no') is-invalid @enderror" value="{{ old('mandi_stall_no', $supplier->mandi_stall_no) }}">
                    @error('mandi_stall_no')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Phone & Mobile -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Office / Landline Phone') }}</label>
                    <input type="text" name="phone" class="form-control font-monospace @error('phone') is-invalid @enderror" value="{{ old('phone', $supplier->phone) }}">
                    @error('phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mobile / WhatsApp') }}</label>
                    <input type="text" name="mobile" class="form-control font-monospace @error('mobile') is-invalid @enderror" value="{{ old('mobile', $supplier->mobile) }}">
                    @error('mobile')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Address & City -->
                  <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">{{ __('Address') }}</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $supplier->address) }}">
                    @error('address')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('City / Mandi Location') }}</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $supplier->city) }}">
                    @error('city')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Notes -->
                  <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Notes & Terms') }}</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $supplier->notes) }}</textarea>
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
                <h6 class="card-title mb-0 fw-bold">{{ __('Financial Terms') }}</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Commission Rate (%)') }}</label>
                  <div class="input-group">
                    <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control @error('commission_rate') is-invalid @enderror" value="{{ old('commission_rate', (float) $supplier->commission_rate) }}">
                    <span class="input-group-text bg-light">%</span>
                  </div>
                  @error('commission_rate')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Payment Terms (Days Credit)') }}</label>
                  <div class="input-group">
                    <input type="number" name="payment_terms_days" class="form-control @error('payment_terms_days') is-invalid @enderror" value="{{ old('payment_terms_days', $supplier->payment_terms_days) }}" min="0">
                    <span class="input-group-text bg-light">{{ __('Days') }}</span>
                  </div>
                  @error('payment_terms_days')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="p-3 bg-light rounded text-center">
                  <small class="text-muted text-uppercase fw-semibold">{{ __('Current Running Balance') }}</small>
                  <h4 class="mb-0 fw-bold font-monospace mt-1 {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $tenant->currency }} {{ number_format($supplier->current_balance, 2) }}
                  </h4>
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
                  <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $supplier->bank_name) }}">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Account Title') }}</label>
                  <input type="text" name="bank_account_title" class="form-control" value="{{ old('bank_account_title', $supplier->bank_account_title) }}">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Account Number / IBAN') }}</label>
                  <input type="text" name="bank_account_number" class="form-control font-monospace" value="{{ old('bank_account_number', $supplier->bank_account_number) }}">
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Supplier Account') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Update Supplier Profile') }}</button>
              <a href="{{ route('tenant.suppliers.show', $supplier) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

