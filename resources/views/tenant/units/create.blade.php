@extends('layouts.admin')

@section('title', __('Add Measurement Unit'))

@section('content')
  <x-breadcrumb 
    :title="__('Add Measurement Unit')" 
    :items="[
      __('Master Data') => '#',
      __('Units') => route('tenant.units.index'),
      __('Create') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Measurement Unit Details') }}</h6>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('tenant.units.store') }}">
            @csrf

            <div class="row g-3">
              <!-- Name English -->
              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Unit Name (English)') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Crate, Bundle, Basket" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Name Urdu -->
              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Unit Name (Urdu)') }}</label>
                <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur') }}" placeholder="مثال: کریٹ، گٹھی، ٹوکری">
                @error('name_ur')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Code -->
              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Unit Code / Symbol') }} <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. crt, bndl, bsk" required>
                <small class="text-muted">{{ __('Short unique code used on receipts and scales.') }}</small>
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Fractional switch -->
              <div class="col-12">
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_fractional" id="isFractionalSwitch" value="1" {{ old('is_fractional') ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isFractionalSwitch">{{ __('Allow decimal / fractional quantities (e.g. 1.250 kg)') }}</label>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
              <a href="{{ route('tenant.units.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
              <button type="submit" class="btn btn-primary px-4">{{ __('Save Unit') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

