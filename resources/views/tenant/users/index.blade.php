@extends('layouts.admin')

@section('title', 'Staff & User Management')

@section('content')
  <x-breadcrumb 
    title="Staff & User Management" 
    :items="[
      'Tenant Operations' => '#',
      'Staff & Roles' => '#',
      'Users' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.users.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-person-plus"></i>
        <span>Add New Staff Member</span>
      </a>
      <a href="{{ route('tenant.roles.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-shield-check"></i>
        <span>Roles & Permissions</span>
      </a>
      <a href="{{ route('tenant.audit.index') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-clock-history"></i>
        <span>Audit Trail</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.users.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input 
              type="text" 
              name="search" 
              value="{{ request('search') }}" 
              class="form-control form-control-sm border-start-0" 
              placeholder="Search staff by name, email or phone..."
            >
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select name="role" class="form-select form-select-sm">
            <option value="">All Roles</option>
            @foreach($roles as $r)
              <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
        </div>
        @if(request()->hasAny(['search', 'role']))
          <div class="col-12 col-md-2">
            <a href="{{ route('tenant.users.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  <!-- Users Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Employees & Staff Accounts</h6>
      <span class="badge bg-light text-dark">{{ $users->total() }} Members</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Staff Name</th>
            <th>Email & Phone</th>
            <th>Role(s)</th>
            <th>Assigned Branches</th>
            <th>Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            <tr>
              <td class="ps-3">
                <div class="fw-semibold text-dark">{{ $u->name }}</div>
                @if($u->is_tenant_owner)
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Business Owner</span>
                @endif
              </td>
              <td>
                <div>{{ $u->email }}</div>
                <small class="text-muted">{{ $u->phone ?? 'No phone' }}</small>
              </td>
              <td>
                @forelse($u->roles as $role)
                  <span class="badge bg-light text-dark border me-1">{{ $role->name }}</span>
                @empty
                  <span class="text-muted small">No Role Assigned</span>
                @endforelse
              </td>
              <td>
                @forelse($u->branches as $branch)
                  <span class="badge bg-secondary-subtle text-secondary small me-1">
                    {{ $branch->name }}
                    @if($branch->pivot?->is_default)
                      <i class="bi bi-star-fill text-warning" title="Default Branch"></i>
                    @endif
                  </span>
                @empty
                  <span class="text-danger small">No branch assigned</span>
                @endforelse
              </td>
              <td>
                @if($u->status === 'active')
                  <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                @elseif($u->status === 'suspended')
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.users.edit', $u->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Employee">
                    <i class="bi bi-pencil"></i>
                  </a>
                  @if(! $u->is_tenant_owner)
                    <form method="POST" action="{{ route('tenant.users.toggle-status', $u->id) }}" class="d-inline" onsubmit="return confirm('Toggle status for this user?');">
                      @csrf
                      <button type="submit" class="btn btn-sm {{ $u->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $u->status === 'active' ? 'Suspend User' : 'Activate User' }}">
                        <i class="bi {{ $u->status === 'active' ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-people fs-2 d-block mb-2"></i>
                No staff members found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($users->hasPages())
      <div class="card-footer bg-white border-0 py-3">
        {{ $users->links() }}
      </div>
    @endif
  </div>
@endsection

