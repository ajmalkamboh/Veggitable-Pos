@extends('layouts.admin')

@section('title', 'Edit Business: ' . $tenant->name)

@section('content')
  <x-breadcrumb 
    :title="'Edit: ' . $tenant->name" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Businesses' => route('superadmin.tenants.index'),
      $tenant->slug => route('superadmin.tenants.show', $tenant->id),
      'Edit' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.tenants.show', $tenant->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Business
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row">
    <div class="col-lg-8 mx-auto">
      <x-card title="Edit Business Profile" :subtitle="'Tenant ID #' . $tenant->id">
        <form method="POST" action="{{ route('superadmin.tenants.update', $tenant->id) }}">
          @csrf
          @method('PUT')

          <x-form-input 
            name="name" 
            label="Business Name" 
            :value="$tenant->name" 
            required 
          />

          <div class="row g-2">
            <div class="col-md-6">
              <x-form-input 
                name="email" 
                type="email" 
                label="Official Email" 
                :value="$tenant->email" 
              />
            </div>
            <div class="col-md-6">
              <x-form-input 
                name="phone" 
                label="Phone Number" 
                :value="$tenant->phone" 
              />
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <x-form-input 
                name="city" 
                label="City / Market" 
                :value="$tenant->city" 
              />
            </div>
            <div class="col-md-6">
              <x-form-input 
                name="address" 
                label="Address / Shop Location" 
                :value="$tenant->address" 
              />
            </div>
          </div>

          <div class="row g-2">
            <div class="col-md-4">
              <x-form-select 
                name="currency" 
                label="Currency" 
                :options="['PKR' => 'PKR - Pakistani Rupee', 'USD' => 'USD - Dollar', 'SAR' => 'SAR - Riyal', 'AED' => 'AED - Dirham']"
                :selected="$tenant->currency"
                required 
              />
            </div>
            <div class="col-md-4">
              <x-form-input 
                name="timezone" 
                label="Timezone" 
                :value="$tenant->timezone" 
                required 
              />
            </div>
            <div class="col-md-4">
              <x-form-select 
                name="default_locale" 
                label="Default Language" 
                :options="['en' => 'English (LTR)', 'ur' => 'اردو (Urdu RTL)']"
                :selected="$tenant->default_locale"
                required 
              />
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('superadmin.tenants.show', $tenant->id) }}" class="btn btn-secondary btn-sm">Cancel</a>
            <x-button type="submit" variant="primary" size="sm">Save Changes</x-button>
          </div>
        </form>
      </x-card>
    </div>
  </div>
@endsection

