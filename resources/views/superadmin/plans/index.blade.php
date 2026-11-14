@extends('layouts.admin')

@section('title', 'Subscription Plans & Tiers - Super Admin')

@section('content')
  <x-breadcrumb 
    title="Subscription Plans & Tiers" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Plans' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>Create New Plan</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Plans Pricing Grid -->
  <div class="row g-4 mb-4">
    @forelse($plans as $plan)
      <div class="col-lg-3 col-md-6">
        <div class="card h-100 shadow-sm border-0 position-relative {{ $plan->is_popular ? 'border border-primary' : '' }}">
          @if($plan->is_popular)
            <div class="position-absolute top-0 end-0 m-3">
              <span class="badge bg-primary">Popular</span>
            </div>
          @endif

          <div class="card-body p-4 d-flex flex-column">
            <h5 class="fw-bold mb-1">{{ $plan->name }}</h5>
            <span class="text-muted small mb-3">{{ $plan->description ?? 'Vegetable Shop SaaS tier' }}</span>

            <div class="mb-4">
              <span class="fs-2 fw-bold text-primary">Rs. {{ number_format($plan->price_monthly, 0) }}</span>
              <span class="text-muted small">/ month</span>
              <div class="small text-muted">Rs. {{ number_format($plan->price_yearly, 0) }} billed yearly</div>
            </div>

            <!-- Limits -->
            <ul class="list-unstyled small mb-4">
              <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-check2 text-success me-2 fs-6"></i>
                <span><strong>{{ $plan->max_branches == -1 ? 'Unlimited' : $plan->max_branches }}</strong> Branch Outlet(s)</span>
              </li>
              <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-check2 text-success me-2 fs-6"></i>
                <span><strong>{{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }}</strong> User / Staff Accounts</span>
              </li>
              <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-check2 text-success me-2 fs-6"></i>
                <span><strong>{{ $plan->max_products == -1 ? 'Unlimited' : $plan->max_products }}</strong> Products Catalog</span>
              </li>
              <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-clock-history text-info me-2 fs-6"></i>
                <span>{{ $plan->trial_days }} Days Free Trial</span>
              </li>
              <li class="mb-2 d-flex align-items-center">
                <i class="bi bi-hourglass-split text-warning me-2 fs-6"></i>
                <span>{{ $plan->grace_days }} Days Grace Period</span>
              </li>
            </ul>

            <!-- Features Tags -->
            <div class="mb-4 flex-grow-1">
              <span class="text-uppercase small fw-semibold text-muted d-block mb-2">Entitlements:</span>
              <div class="d-flex flex-wrap gap-1">
                @foreach($plan->features ?? [] as $featKey)
                  <span class="badge bg-light text-dark border small">{{ $featKey }}</span>
                @endforeach
              </div>
            </div>

            <div class="border-top pt-3 d-flex align-items-center justify-content-between mt-auto">
              <span class="small text-muted">
                <strong>{{ $plan->tenants_count }}</strong> Subscribers
              </span>
              <div class="d-flex align-items-center gap-1">
                <a href="{{ route('superadmin.plans.edit', $plan->id) }}" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-pencil"></i> Edit
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5">
        <p class="text-muted">No subscription plans created yet. Click "Create New Plan" to set up your tiers.</p>
      </div>
    @endforelse
  </div>
@endsection

