@extends('layouts.admin')

@section('title', 'Add New Staff Member')

@section('content')
  <x-breadcrumb 
    title="Add Staff Member" 
    :items="[
      'Tenant Operations' => '#',
      'Staff' => route('tenant.users.index'),
      'New Employee' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Staff List</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="mb-0 fw-bold">Employee Account Details</h6>
          <small class="text-muted">Create login credentials, assign operating branches, and grant role permissions.</small>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('tenant.users.store') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3">
              <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                class="form-control @error('name') is-invalid @enderror" 
                value="{{ old('name') }}" 
                placeholder="e.g. Tariq Mehmood" 
                required
              >
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Email & Phone -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input 
                  type="email" 
                  name="email" 
                  id="email" 
                  class="form-control @error('email') is-invalid @enderror" 
                  value="{{ old('email') }}" 
                  placeholder="e.g. tariq@shop.test" 
                  required
                >
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                <input 
                  type="text" 
                  name="phone" 
                  id="phone" 
                  class="form-control @error('phone') is-invalid @enderror" 
                  value="{{ old('phone') }}" 
                  placeholder="e.g. +92 300 1234567"
                >
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
              <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <input 
                type="password" 
                name="password" 
                id="password" 
                class="form-control @error('password') is-invalid @enderror" 
                placeholder="Minimum 8 characters with letters & numbers" 
                required
              >
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">Must be at least 8 characters long containing letters and numbers.</div>
            </div>

            <!-- Branch Assignments -->
            <div class="mb-4">
              <label class="form-label fw-semibold d-block">Authorized Branches / Outlets <span class="text-danger">*</span></label>
              <div class="border rounded p-3 bg-light">
                <div class="row g-2">
                  @foreach($branches as $index => $branch)
                    <div class="col-sm-6">
                      <div class="form-check">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          name="branch_ids[]" 
                          value="{{ $branch->id }}" 
                          id="branch_{{ $branch->id }}" 
                          {{ is_array(old('branch_ids')) && in_array($branch->id, old('branch_ids')) ? 'checked' : ($index === 0 ? 'checked' : '') }}
                        >
                        <label class="form-check-label small" for="branch_{{ $branch->id }}">
                          <strong>{{ $branch->name }}</strong> ({{ $branch->code }})
                          @if($branch->is_headquarters)
                            <span class="badge bg-primary-subtle text-primary ms-1">HQ</span>
                          @endif
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              @error('branch_ids')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
              <div class="form-text">The first selected branch will be configured as the user's default active branch.</div>
            </div>

            <!-- Role Assignments -->
            <div class="mb-4">
              <label class="form-label fw-semibold d-block">System / Operational Role <span class="text-danger">*</span></label>
              <div class="border rounded p-3">
                <div class="row g-3">
                  @foreach($roles as $role)
                    <div class="col-md-6">
                      <div class="form-check">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          name="role_ids[]" 
                          value="{{ $role->id }}" 
                          id="role_{{ $role->id }}" 
                          {{ is_array(old('role_ids')) && in_array($role->id, old('role_ids')) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="role_{{ $role->id }}">
                          <div class="fw-semibold">{{ $role->name }}</div>
                          <small class="text-muted d-block">{{ $role->description }}</small>
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              @error('role_ids')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('tenant.users.index') }}" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <i class="bi bi-person-check"></i>
                <span>Save Staff Member</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

