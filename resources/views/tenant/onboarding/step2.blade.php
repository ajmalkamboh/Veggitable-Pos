@extends('layouts.admin')

@section('title', 'Onboarding Step 2: Branch Setup')

@section('content')
  <x-breadcrumb 
    title="Step 2: Primary Shop Outlet / Branch" 
    :items="[
      'Business Setup' => route('tenant.onboarding.index'),
      'Step 2: Branch' => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">2. Primary Shop Outlet / Branch</h6>
            <span class="badge bg-primary">Step 2 of 4</span>
          </div>
          <small class="text-muted">Set up your main operating shop counter, Mandi shed, or cold store warehouse.</small>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('tenant.onboarding.step2.save') }}">
            @csrf

            <!-- Branch Name -->
            <div class="mb-3">
              <label for="branch_name" class="form-label fw-semibold">Branch / Outlet Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                name="branch_name" 
                id="branch_name" 
                class="form-control @error('branch_name') is-invalid @enderror" 
                value="{{ old('branch_name', $headquarters?->name ?? 'Main Outlet') }}" 
                placeholder="e.g. Main Market Head Branch" 
                required
              >
              @error('branch_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Branch Code & City -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="branch_code" class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="branch_code" 
                  id="branch_code" 
                  class="form-control font-monospace @error('branch_code') is-invalid @enderror" 
                  value="{{ old('branch_code', $headquarters?->code ?? 'HQ-01') }}" 
                  placeholder="e.g. HQ-01" 
                  required
                >
                @error('branch_code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Short alphanumeric code used on barcode stickers and invoices.</div>
              </div>
              <div class="col-md-6">
                <label for="city" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  name="city" 
                  id="city" 
                  class="form-control @error('city') is-invalid @enderror" 
                  value="{{ old('city', $headquarters?->city ?? $tenant->city) }}" 
                  required
                >
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Address & Phone -->
            <div class="row g-3 mb-4">
              <div class="col-md-7">
                <label for="address" class="form-label fw-semibold">Physical Location / Shed No.</label>
                <input 
                  type="text" 
                  name="address" 
                  id="address" 
                  class="form-control @error('address') is-invalid @enderror" 
                  value="{{ old('address', $headquarters?->address ?? $tenant->address) }}" 
                  placeholder="e.g. Counter 4, Fresh Hall"
                >
                @error('address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-5">
                <label for="phone" class="form-label fw-semibold">Counter Phone</label>
                <input 
                  type="text" 
                  name="phone" 
                  id="phone" 
                  class="form-control @error('phone') is-invalid @enderror" 
                  value="{{ old('phone', $headquarters?->phone ?? $tenant->phone) }}"
                >
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <a href="{{ route('tenant.onboarding.step1') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Step 1
              </a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <span>Save Branch & Continue to Step 3</span>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

