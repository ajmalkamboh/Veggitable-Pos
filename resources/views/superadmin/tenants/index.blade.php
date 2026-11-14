@extends('layouts.admin')

@section('title', 'Manage Businesses & Tenants - Super Admin')

@section('content')
  <x-breadcrumb 
    title="Businesses & Merchants" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Businesses' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.register.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>Register New Business</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('superadmin.tenants.index') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by name, slug, email, or city..." value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select form-select-sm">
            <option value="">-- All Statuses --</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="plan_id" class="form-select form-select-sm">
            <option value="">-- All Plans --</option>
            @foreach($plans as $p)
              <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tenants Table -->
  <x-card title="Registered Vegetable Businesses" :subtitle="'Showing ' . $tenants->total() . ' total businesses'">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Business Name</th>
            <th>Owner Info</th>
            <th>City</th>
            <th>Subscription / Plan</th>
            <th>Status</th>
            <th>Branches</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tenants as $t)
            <tr>
              <td>
                <div class="fw-bold">{{ $t->name }}</div>
                <span class="small text-muted">ID: <code>#{{ $t->id }}</code> | Slug: <code>{{ $t->slug }}</code></span>
              </td>
              <td>
                <div class="small fw-semibold">{{ $t->owner?->name ?? 'No Owner' }}</div>
                <span class="small text-muted">{{ $t->email ?? $t->owner?->email }}</span>
              </td>
              <td>{{ $t->city ?? 'N/A' }}</td>
              <td>
                @if($t->plan)
                  <span class="badge bg-light text-dark border">{{ $t->plan->name }}</span>
                @else
                  <span class="badge bg-info-subtle text-info">Trial</span>
                @endif
                <div class="small text-muted mt-1">
                  Ends: {{ $t->subscription_ends_at ? $t->subscription_ends_at->format('M d, Y') : ($t->trial_ends_at ? $t->trial_ends_at->format('M d, Y') : 'N/A') }}
                </div>
              </td>
              <td>
                <span class="badge {{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>
              </td>
              <td>{{ $t->branches->count() }}</td>
              <td class="text-end">
                <div class="d-inline-flex align-items-center gap-1">
                  <!-- One-Click Suspend / Activate Toggle -->
                  <form method="POST" action="{{ route('superadmin.tenants.toggle-status', $t->id) }}" onsubmit="return confirm('Are you sure you want to change status for {{ $t->name }}?');">
                    @csrf
                    @if($t->status->isActive())
                      <button type="submit" class="btn btn-xs btn-outline-danger btn-sm" title="Suspend Business">
                        <i class="bi bi-slash-circle"></i> Suspend
                      </button>
                    @else
                      <button type="submit" class="btn btn-xs btn-outline-success btn-sm" title="Activate Business">
                        <i class="bi bi-check-circle"></i> Activate
                      </button>
                    @endif
                  </form>

                  <a href="{{ route('superadmin.tenants.show', $t->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                  </a>
                  <a href="{{ route('superadmin.tenants.edit', $t->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No businesses found matching your criteria.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $tenants->links() }}
    </div>
  </x-card>
@endsection

