@extends('layouts.admin')

@section('title', 'Edit Staff Member: ' . $user->name)

@section('content')
  <x-breadcrumb 
    title="Edit Staff Member" 
    :items="[
      'Tenant Operations' => '#',
      'Staff' => route('tenant.users.index'),
      $user->name => '#'
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
          <h6 class="mb-0 fw-bold">Update Employee Profile</h6>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('tenant.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-3">
              <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                class="form-control @error('name') is-invalid @enderror" 
                value="{{ old('name', $user->name) }}" 
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
                  value="{{ old('email', $user->email) }}" 
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
                  value="{{ old('phone', $user->phone) }}"
                >
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Status & Reset Password -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="status" class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                  <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                  <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                  <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label for="password" class="form-label fw-semibold">New Password (Optional)</label>
                <input 
                  type="password" 
                  name="password" 
                  id="password" 
                  class="form-control @error('password') is-invalid @enderror" 
                  placeholder="Leave blank to keep current password"
                >
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Branch Assignments -->
            <div class="mb-4">
              <label class="form-label fw-semibold d-block">Authorized Branches / Outlets <span class="text-danger">*</span></label>
              <div class="border rounded p-3 bg-light">
                <div class="row g-2">
                  @foreach($branches as $branch)
                    <div class="col-sm-6">
                      <div class="form-check">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          name="branch_ids[]" 
                          value="{{ $branch->id }}" 
                          id="branch_{{ $branch->id }}" 
                          {{ in_array($branch->id, old('branch_ids', $assignedBranchIds)) ? 'checked' : '' }}
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
            </div>

            <!-- Role Assignments -->
            <div class="mb-4">
              <label class="form-label fw-semibold d-block">Role Assignment <span class="text-danger">*</span></label>
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
                          {{ in_array($role->id, old('role_ids', $assignedRoleIds)) ? 'checked' : '' }}
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
                <i class="bi bi-check2-circle"></i>
                <span>Update Employee</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

