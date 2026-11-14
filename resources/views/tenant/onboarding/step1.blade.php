@extends('layouts.admin')

@section('title', 'Onboarding Step 1: Business Profile')

@section('content')
  <x-breadcrumb 
    title="Step 1: Business Profile & Identity" 
    :items="[
      'Business Setup' => route('tenant.onboarding.index'),
      'Step 1: Profile' => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">1. Business Profile & Identity</h6>
            <span class="badge bg-primary">Step 1 of 4</span>
          </div>
          <small class="text-muted">General store details, location, currency, logo, and fiscal year.</small>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('tenant.onboarding.step1.update') }}" enctype="multipart/form-data">
            @csrf

            <!-- Business Name -->
            <div class="mb-3">
              <label for="name" class="form-label fw-semibold">Registered Business Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                class="form-control @error('name') is-invalid @enderror" 
                value="{{ old('name', $tenant->name) }}" 
                required
              >
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Email & Phone -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Business Email</label>
                <input 
                  type="email" 
                  name="email" 
                  id="email" 
                  class="form-control @error('email') is-invalid @enderror" 
                  value="{{ old('email', $tenant->email) }}"
                >
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="phone" class="form-label fw-semibold">Phone / WhatsApp <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="phone" 
                  id="phone" 
                  class="form-control @error('phone') is-invalid @enderror" 
                  value="{{ old('phone', $tenant->phone) }}" 
                  required
                >
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Address & City -->
            <div class="row g-3 mb-3">
              <div class="col-md-8">
                <label for="address" class="form-label fw-semibold">Shop / Market Address <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="address" 
                  id="address" 
                  class="form-control @error('address') is-invalid @enderror" 
                  value="{{ old('address', $tenant->address) }}" 
                  placeholder="e.g. Shop 12-B, Main Subzi Mandi" 
                  required
                >
                @error('address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="city" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="city" 
                  id="city" 
                  class="form-control @error('city') is-invalid @enderror" 
                  value="{{ old('city', $tenant->city) }}" 
                  required
                >
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Currency, Language, Timezone -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label for="currency" class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror">
                  <option value="PKR" {{ old('currency', $tenant->currency) === 'PKR' ? 'selected' : '' }}>PKR (Rs.)</option>
                  <option value="USD" {{ old('currency', $tenant->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                  <option value="AED" {{ old('currency', $tenant->currency) === 'AED' ? 'selected' : '' }}>AED (د.إ)</option>
                  <option value="SAR" {{ old('currency', $tenant->currency) === 'SAR' ? 'selected' : '' }}>SAR (﷼)</option>
                </select>
                @error('currency')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="default_locale" class="form-label fw-semibold">Default Language <span class="text-danger">*</span></label>
                <select name="default_locale" id="default_locale" class="form-select @error('default_locale') is-invalid @enderror">
                  <option value="ur" {{ old('default_locale', $tenant->default_locale) === 'ur' ? 'selected' : '' }}>Urdu (اردو)</option>
                  <option value="en" {{ old('default_locale', $tenant->default_locale) === 'en' ? 'selected' : '' }}>English</option>
                </select>
                @error('default_locale')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label for="timezone" class="form-label fw-semibold">Timezone <span class="text-danger">*</span></label>
                <select name="timezone" id="timezone" class="form-select @error('timezone') is-invalid @enderror">
                  <option value="Asia/Karachi" {{ old('timezone', $tenant->timezone) === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                  <option value="Asia/Dubai" {{ old('timezone', $tenant->timezone) === 'Asia/Dubai' ? 'selected' : '' }}>Asia/Dubai (GST)</option>
                  <option value="UTC" {{ old('timezone', $tenant->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                </select>
                @error('timezone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Fiscal Year & Opening Date -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="financial_year_start" class="form-label fw-semibold">Financial Year Start <span class="text-danger">*</span></label>
                <select name="financial_year_start" id="financial_year_start" class="form-select @error('financial_year_start') is-invalid @enderror">
                  <option value="07-01" {{ old('financial_year_start', $tenant->financial_year_start) === '07-01' ? 'selected' : '' }}>July 1st (Pakistan Standard Fiscal Year)</option>
                  <option value="01-01" {{ old('financial_year_start', $tenant->financial_year_start) === '01-01' ? 'selected' : '' }}>January 1st (Calendar Year)</option>
                </select>
                @error('financial_year_start')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="business_opening_date" class="form-label fw-semibold">Business Opening Date</label>
                <input 
                  type="date" 
                  name="business_opening_date" 
                  id="business_opening_date" 
                  class="form-control @error('business_opening_date') is-invalid @enderror" 
                  value="{{ old('business_opening_date', $tenant->business_opening_date?->format('Y-m-d')) }}"
                >
                @error('business_opening_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Logo Upload -->
            <div class="mb-4">
              <label for="logo" class="form-label fw-semibold">Shop Logo (Optional)</label>
              <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
              @error('logo')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">Will appear on receipt headers and invoices. Max 2MB (PNG, JPG, WebP).</div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <a href="{{ route('tenant.onboarding.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Checklist
              </a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <span>Save & Continue to Step 2</span>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
