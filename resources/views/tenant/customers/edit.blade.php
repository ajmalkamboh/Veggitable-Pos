@extends('layouts.admin')

@section('title', __('Edit Customer'))

@section('content')
  <x-breadcrumb 
    :title="__('Edit Customer')" 
    :items="[
      __('Parties') => '#',
      __('Customers') => route('tenant.customers.index'),
      $customer->name => route('tenant.customers.show', $customer),
      __('Edit') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.customers.update', $customer) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <!-- Main Customer Profile -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">{{ __('Customer / Business Information') }}</h6>
                <span class="badge bg-light text-dark font-monospace">{{ $customer->code }}</span>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer / Business Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Name (Urdu)') }}</label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur', $customer->name_ur) }}">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Customer Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Category') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                      @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type', $customer->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                    @error('type')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Code -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Customer Code / Account No.') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code', $customer->code) }}" required>
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Contact Person -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Contact Person / Manager') }}</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person', $customer->contact_person) }}">
                    @error('contact_person')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- NTN / Tax ID -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('NTN / CNIC / Tax ID') }}</label>
                    <input type="text" name="tax_id" class="form-control font-monospace @error('tax_id') is-invalid @enderror" value="{{ old('tax_id', $customer->tax_id) }}">
                    @error('tax_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Phone & Mobile -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Telephone / Office') }}</label>
                    <input type="text" name="phone" class="form-control font-monospace @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->phone) }}">
                    @error('phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Mobile / WhatsApp') }}</label>
                    <input type="text" name="mobile" class="form-control font-monospace @error('mobile') is-invalid @enderror" value="{{ old('mobile', $customer->mobile) }}">
                    @error('mobile')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Address & City -->
                  <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">{{ __('Delivery Address') }}</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $customer->address) }}">
                    @error('address')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('City') }}</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $customer->city) }}">
                    @error('city')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Notes -->
                  <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('Notes & Instructions') }}</label>
                    <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $customer->notes) }}</textarea>
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
                <h6 class="card-title mb-0 fw-bold">{{ __('Credit Limit & Terms') }}</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Maximum Credit Limit') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', (float) $customer->credit_limit) }}">
                  </div>
                  @error('credit_limit')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Allowed Credit Days') }}</label>
                  <div class="input-group">
                    <input type="number" name="credit_days" class="form-control @error('credit_days') is-invalid @enderror" value="{{ old('credit_days', $customer->credit_days) }}" min="0">
                    <span class="input-group-text bg-light">{{ __('Days') }}</span>
                  </div>
                  @error('credit_days')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="p-3 bg-light rounded text-center mb-3">
                  <small class="text-muted text-uppercase fw-semibold">{{ __('Current Outstanding Receivable') }}</small>
                  <h4 class="mb-0 fw-bold font-monospace mt-1 {{ $customer->current_balance > 0 ? 'text-primary' : 'text-success' }}">
                    {{ $tenant->currency }} {{ number_format($customer->current_balance, 2) }}
                  </h4>
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Customer Account') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Update Customer Account') }}</button>
              <a href="{{ route('tenant.customers.show', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

