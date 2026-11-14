@extends('layouts.admin')

@section('title', 'Edit Custom Role: ' . $role->name)

@section('content')
  <x-breadcrumb 
    title="Edit Custom Role" 
    :items="[
      'Tenant Operations' => '#',
      'Roles' => route('tenant.roles.index'),
      $role->name => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.roles.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Roles</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <form method="POST" action="{{ route('tenant.roles.update', $role->id) }}">
    @csrf
    @method('PUT')

    <div class="row g-4 mb-4">
      <!-- Role Meta Card -->
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold">Role Information</h6>
          </div>
          <div class="card-body">
            <!-- Name -->
            <div class="mb-3">
              <label for="name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                class="form-control @error('name') is-invalid @enderror" 
                value="{{ old('name', $role->name) }}" 
                required
              >
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Slug -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Role Slug</label>
              <input 
                type="text" 
                class="form-control font-monospace bg-light" 
                value="{{ $role->slug }}" 
                readonly
              >
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="description" class="form-label fw-semibold">Description</label>
              <textarea 
                name="description" 
                id="description" 
                rows="3" 
                class="form-control @error('description') is-invalid @enderror" 
              >{{ old('description', $role->description) }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-1">
              <i class="bi bi-check2-circle"></i>
              <span>Update Role Permissions</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Permissions Selection Card -->
      <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Configure Module Permissions <span class="text-danger">*</span></h6>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllPermissions()">Toggle All</button>
          </div>
          <div class="card-body p-4">
            @error('permission_ids')
              <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
            @enderror

            @foreach($permissionsByModule as $module => $modulePermissions)
              <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                  <h6 class="fw-bold mb-0 text-uppercase small text-primary">
                    <i class="bi bi-folder-fill me-1"></i> {{ $module }} Module
                  </h6>
                  <span class="badge bg-white text-muted border">{{ $modulePermissions->count() }} Actions</span>
                </div>
                <div class="row g-2">
                  @foreach($modulePermissions as $p)
                    <div class="col-12 col-md-6">
                      <div class="form-check">
                        <input 
                          class="form-check-input perm-checkbox" 
                          type="checkbox" 
                          name="permission_ids[]" 
                          value="{{ $p->id }}" 
                          id="perm_{{ $p->id }}" 
                          {{ in_array($p->id, old('permission_ids', $assignedPermissionIds)) ? 'checked' : '' }}
                        >
                        <label class="form-check-label small" for="perm_{{ $p->id }}">
                          <span class="fw-semibold">{{ $p->name }}</span>
                          <span class="d-block text-muted" style="font-size: 0.75rem;">{{ $p->description }}</span>
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    function toggleAllPermissions() {
      const boxes = document.querySelectorAll('.perm-checkbox');
      const allChecked = Array.from(boxes).every(b => b.checked);
      boxes.forEach(b => b.checked = !allChecked);
    }
  </script>
@endsection

