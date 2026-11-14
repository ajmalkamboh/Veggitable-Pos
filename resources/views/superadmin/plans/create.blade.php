@extends('layouts.admin')

@section('title', 'Create Subscription Plan - Super Admin')

@section('content')
  <x-breadcrumb 
    title="Create Subscription Plan" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Plans' => route('superadmin.plans.index'),
      'Create' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.plans.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Plans
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row">
    <div class="col-lg-9 mx-auto">
      <x-card title="Plan Details & Pricing" subtitle="Configure tier pricing, branch limits, and feature entitlements">
        <form method="POST" action="{{ route('superadmin.plans.store') }}">
          @csrf

          <div class="row g-2">
            <div class="col-md-7">
              <x-form-input 
                name="name" 
                label="Plan Name" 
                placeholder="e.g. Standard Plan" 
                required 
              />
            </div>
            <div class="col-md-5">
              <x-form-input 
                name="slug" 
                label="Slug / Identifier (Optional)" 
                placeholder="e.g. standard" 
                help="Auto-generated if left blank" 
              />
            </div>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label small fw-medium">Description</label>
            <textarea name="description" id="description" rows="2" class="form-control" placeholder="Brief summary of plan target audience..."></textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <x-form-input 
                type="number" 
                step="0.01" 
                name="price_monthly" 
                label="Monthly Price (Rs.)" 
                placeholder="2500.00" 
                required 
              />
            </div>
            <div class="col-md-6">
              <x-form-input 
                type="number" 
                step="0.01" 
                name="price_yearly" 
                label="Yearly Price (Rs.)" 
                placeholder="25000.00" 
                required 
              />
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="max_branches" 
                label="Max Branches (-1 for unltd)" 
                value="1" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="max_users" 
                label="Max Users (-1 for unltd)" 
                value="2" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="trial_days" 
                label="Trial Days" 
                value="14" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="grace_days" 
                label="Grace Days" 
                value="3" 
                required 
              />
            </div>
          </div>

          <h6 class="text-primary fw-semibold border-bottom pb-2 mt-4 mb-3">
            <i class="bi bi-toggles me-1"></i> Configurable Feature Entitlements
          </h6>
          <div class="row g-3">
            @foreach($availableFeatures as $key => $label)
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}" id="feat_{{ $key }}" checked>
                  <label class="form-check-label fw-medium small" for="feat_{{ $key }}">
                    {{ $label }}
                    <span class="text-muted d-block small">Key: <code>{{ $key }}</code></span>
                  </label>
                </div>
              </div>
            @endforeach
          </div>

          <div class="row g-3 mt-3 border-top pt-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="is_popular">
                <label class="form-check-label small fw-medium" for="is_popular">Mark as Featured / Popular Plan</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                <label class="form-check-label small fw-medium" for="is_active">Plan is Active & Available</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
            <x-button type="submit" variant="primary" size="sm">Create Plan</x-button>
          </div>
        </form>
      </x-card>
    </div>
  </div>
@endsection

