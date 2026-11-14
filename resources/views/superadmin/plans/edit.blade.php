@extends('layouts.admin')

@section('title', 'Edit Plan: ' . $plan->name)

@section('content')
  <x-breadcrumb 
    :title="'Edit Plan: ' . $plan->name" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Plans' => route('superadmin.plans.index'),
      'Edit' => '#'
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
      <x-card :title="'Edit ' . $plan->name" subtitle="Configure tier pricing, limits, and feature entitlements">
        <form method="POST" action="{{ route('superadmin.plans.update', $plan->id) }}">
          @csrf
          @method('PUT')

          <div class="row g-2">
            <div class="col-md-7">
              <x-form-input 
                name="name" 
                label="Plan Name" 
                :value="$plan->name" 
                required 
              />
            </div>
            <div class="col-md-5">
              <x-form-input 
                name="slug" 
                label="Slug / Identifier" 
                :value="$plan->slug" 
                required 
              />
            </div>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label small fw-medium">Description</label>
            <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $plan->description) }}</textarea>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <x-form-input 
                type="number" 
                step="0.01" 
                name="price_monthly" 
                label="Monthly Price (Rs.)" 
                :value="$plan->price_monthly" 
                required 
              />
            </div>
            <div class="col-md-6">
              <x-form-input 
                type="number" 
                step="0.01" 
                name="price_yearly" 
                label="Yearly Price (Rs.)" 
                :value="$plan->price_yearly" 
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
                :value="$plan->max_branches" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="max_users" 
                label="Max Users (-1 for unltd)" 
                :value="$plan->max_users" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="trial_days" 
                label="Trial Days" 
                :value="$plan->trial_days" 
                required 
              />
            </div>
            <div class="col-md-3">
              <x-form-input 
                type="number" 
                name="grace_days" 
                label="Grace Days" 
                :value="$plan->grace_days" 
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
                  <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}" id="feat_{{ $key }}" {{ $plan->hasFeature($key) ? 'checked' : '' }}>
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
                <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="is_popular" {{ $plan->is_popular ? 'checked' : '' }}>
                <label class="form-check-label small fw-medium" for="is_popular">Mark as Featured / Popular Plan</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ $plan->is_active ? 'checked' : '' }}>
                <label class="form-check-label small fw-medium" for="is_active">Plan is Active & Available</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
            <x-button type="submit" variant="primary" size="sm">Save Changes</x-button>
          </div>
        </form>
      </x-card>
    </div>
  </div>
@endsection

