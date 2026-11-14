@extends('layouts.admin')

@section('title', 'Roles & Permissions Matrix')

@section('content')
  <x-breadcrumb 
    title="Roles & Permissions" 
    :items="[
      'Tenant Operations' => '#',
      'Staff' => route('tenant.users.index'),
      'Roles' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.roles.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-shield-plus"></i>
        <span>Create Custom Role</span>
      </a>
      <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-people"></i>
        <span>Staff Accounts</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Roles Grid -->
  <div class="row g-4 mb-4">
    @foreach($roles as $role)
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-1 fw-bold text-dark">{{ $role->name }}</h6>
              <span class="badge bg-light text-dark font-monospace">{{ $role->slug }}</span>
            </div>
            @if($role->is_system)
              <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                <i class="bi bi-lock-fill me-1"></i> System Role
              </span>
            @else
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-star me-1"></i> Custom Role
              </span>
            @endif
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">{{ $role->description ?? 'No role description provided.' }}</p>

            <div class="d-flex justify-content-between align-items-center small text-muted border-top pt-3">
              <span><strong>{{ $role->permissions->count() }}</strong> Permissions granted</span>
              <span><strong>{{ $role->users->count() }}</strong> Staff assigned</span>
            </div>
          </div>
          <div class="card-footer bg-white border-0 py-2 d-flex justify-content-between align-items-center">
            <span class="small text-muted">{{ $role->is_system ? 'Built-in template' : 'Tenant Custom' }}</span>
            @if(! $role->is_system && (int) $role->tenant_id === (int) $tenant->id)
              <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit Role
              </a>
            @else
              <button class="btn btn-sm btn-light border text-muted" disabled title="System roles cannot be modified">
                <i class="bi bi-shield-lock me-1"></i> Locked
              </button>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <!-- Permissions Master Reference -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
      <h6 class="mb-0 fw-bold">Available Platform Permissions by Module</h6>
      <small class="text-muted">Master capability catalogue available for assignment to roles.</small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Module</th>
              <th>Permission Identifier</th>
              <th>Permission Name</th>
              <th>Capability Description</th>
            </tr>
          </thead>
          <tbody>
            @foreach($permissionsByModule as $module => $modulePermissions)
              @foreach($modulePermissions as $p)
                <tr>
                  @if($loop->first)
                    <td rowspan="{{ $modulePermissions->count() }}" class="ps-3 fw-bold align-top bg-light text-uppercase small">
                      <i class="bi bi-folder-fill text-primary me-1"></i> {{ $module }}
                    </td>
                  @endif
                  <td><code class="text-primary">{{ $p->slug }}</code></td>
                  <td class="fw-semibold">{{ $p->name }}</td>
                  <td class="small text-muted">{{ $p->description }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

