@extends('layouts.admin')

@section('title', 'Onboarding Step 4: Units & Categories')

@section('content')
  <x-breadcrumb 
    title="Step 4: Units & Master Categories" 
    :items="[
      'Business Setup' => route('tenant.onboarding.index'),
      'Step 4: Defaults' => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">4. Master Measurement Units & Classifications</h6>
            <span class="badge bg-success">Final Step</span>
          </div>
          <small class="text-muted">We have automatically loaded industry-standard vegetable, fruit, and expense defaults for your shop.</small>
        </div>
        <div class="card-body p-4">
          <!-- 1. Measurement Units -->
          <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3">
              <i class="bi bi-rulers me-1"></i> Standard Measurement Units ({{ $units->count() }})
            </h6>
            <div class="row g-2">
              @foreach($units as $u)
                <div class="col-6 col-md-4">
                  <div class="border rounded p-2 bg-light d-flex justify-content-between align-items-center">
                    <div>
                      <span class="fw-semibold">{{ $u->name }}</span>
                      <small class="text-muted d-block">{{ $u->name_ur }}</small>
                    </div>
                    <span class="badge bg-secondary font-monospace">{{ $u->code }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- 2. Product Categories -->
          <div class="mb-4">
            <h6 class="fw-bold text-success mb-3">
              <i class="bi bi-basket me-1"></i> Pre-configured Product Categories ({{ $productCategories->count() }})
            </h6>
            <div class="row g-2">
              @foreach($productCategories as $pc)
                <div class="col-12 col-md-6">
                  <div class="border rounded p-2 bg-light">
                    <div class="d-flex justify-content-between">
                      <span class="fw-semibold text-dark">{{ $pc->name }}</span>
                      <span class="urdu-font small text-muted">{{ $pc->name_ur }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $pc->description }}</small>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- 3. Expense Categories -->
          <div class="mb-4">
            <h6 class="fw-bold text-warning mb-3">
              <i class="bi bi-receipt-cutoff me-1"></i> Pre-configured Shop Expense Categories ({{ $expenseCategories->count() }})
            </h6>
            <div class="row g-2">
              @foreach($expenseCategories as $ec)
                <div class="col-12 col-md-6">
                  <div class="border rounded p-2 bg-light">
                    <div class="d-flex justify-content-between">
                      <span class="fw-semibold text-dark">{{ $ec->name }}</span>
                      <span class="urdu-font small text-muted">{{ $ec->name_ur }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $ec->description }}</small>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

          <!-- Complete Onboarding Form -->
          <form method="POST" action="{{ route('tenant.onboarding.finish') }}" class="pt-3 border-top">
            @csrf

            <div class="d-flex justify-content-between align-items-center">
              <a href="{{ route('tenant.onboarding.step3') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Step 3
              </a>
              <button type="submit" class="btn btn-success px-4 py-2 fs-6 fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i>
                <span>Complete Business Setup & Unlock Shop Operations</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
