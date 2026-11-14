@extends('layouts.admin')

@section('title', 'Platform Super Admin Dashboard')

@section('content')
  <x-breadcrumb 
    title="SaaS Platform Super Admin" 
    :items="[
      'Platform' => '#',
      'Dashboard' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-buildings"></i>
        <span>Manage Businesses</span>
      </a>
      <a href="{{ route('superadmin.plans.index') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-credit-card"></i>
        <span>Subscription Plans</span>
      </a>
      <a href="{{ route('superadmin.subscriptions.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cash-coin"></i>
        <span>Record Payment</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Top Metrics Row -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Total Businesses" 
        value="{{ $totalTenants }}" 
        icon="bi-buildings" 
        color="primary"
        subtitle="{{ $activeTenants }} Active | {{ $suspendedTenants }} Suspended"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Total Outlets / Branches" 
        value="{{ $totalBranches }}" 
        icon="bi-geo-alt" 
        color="success"
        subtitle="Across all tenants"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Total Revenue Collected" 
        value="Rs. {{ number_format($totalRevenue, 0) }}" 
        icon="bi-cash-stack" 
        color="info"
        change="Rs. {{ number_format($monthlyRevenue, 0) }} 30d"
        changeType="positive"
        subtitle="Subscription Invoices"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Platform Users" 
        value="{{ $totalUsers }}" 
        icon="bi-people" 
        color="warning"
        subtitle="{{ $totalPlans }} Subscription Tiers"
      />
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Recent Businesses -->
    <div class="col-lg-8">
      <x-card title="Recent Registered Businesses" subtitle="Overview of newly joined vegetable merchants">
        <x-slot name="actions">
          <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
        </x-slot>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Business Name</th>
                <th>Owner</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Branches</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentTenants as $t)
                <tr>
                  <td>
                    <div class="fw-bold">{{ $t->name }}</div>
                    <span class="text-muted small">Slug: <code>{{ $t->slug }}</code> | {{ $t->city ?? 'N/A' }}</span>
                  </td>
                  <td>
                    <div class="small fw-semibold">{{ $t->owner?->name ?? 'No Owner' }}</div>
                    <span class="text-muted small">{{ $t->email }}</span>
                  </td>
                  <td>
                    @if($t->plan)
                      <span class="badge bg-light text-dark border">{{ $t->plan->name }}</span>
                    @else
                      <span class="badge bg-info-subtle text-info">Free Trial</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge {{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>
                  </td>
                  <td>{{ $t->branches->count() }}</td>
                  <td class="text-end">
                    <a href="{{ route('superadmin.tenants.show', $t->id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-eye"></i> Manage
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No businesses registered yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </x-card>
    </div>

    <!-- Quick Actions & Revenue Feed -->
    <div class="col-lg-4">
      <x-card title="Recent Subscription Payments" subtitle="Manual and automatic invoice payments">
        <x-slot name="actions">
          <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-sm btn-link text-decoration-none">All</a>
        </x-slot>

        <ul class="list-group list-group-flush small">
          @forelse($recentInvoices as $inv)
            <li class="list-group-item px-0 py-2">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-bold">{{ $inv->tenant?->name ?? 'Unknown Business' }}</span>
                <span class="fw-bold text-success">Rs. {{ number_format($inv->amount, 0) }}</span>
              </div>
              <div class="d-flex align-items-center justify-content-between text-muted small">
                <span><code>{{ $inv->invoice_number }}</code></span>
                <span>{{ $inv->paid_at ? $inv->paid_at->format('M d, Y') : $inv->created_at->format('M d, Y') }}</span>
              </div>
            </li>
          @empty
            <li class="list-group-item px-0 py-3 text-center text-muted">No payment records yet.</li>
          @endforelse
        </ul>

        <div class="mt-3">
          <a href="{{ route('superadmin.subscriptions.create') }}" class="btn btn-outline-primary btn-sm w-100">
            <i class="bi bi-plus-circle me-1"></i> Record New Payment
          </a>
        </div>
      </x-card>
    </div>
  </div>
@endsection

