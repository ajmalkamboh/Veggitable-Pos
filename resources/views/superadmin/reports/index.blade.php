@extends('layouts.admin')

@section('title', 'Platform SaaS Reports & Metrics')

@section('content')
  <x-breadcrumb 
    title="Platform SaaS Reports" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Reports' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-buildings"></i>
        <span>Businesses</span>
      </a>
      <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-receipt"></i>
        <span>Invoices</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Top Revenue & Platform Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Total Platform Revenue" 
        value="PKR {{ number_format($totalRevenue, 2) }}" 
        icon="bi-cash-stack" 
        color="success"
        subtitle="Lifetime collection"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="30-Day Revenue" 
        value="PKR {{ number_format($monthlyRevenue, 2) }}" 
        icon="bi-graph-up-arrow" 
        color="primary"
        subtitle="Trailing 30 days"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Total Subscriptions" 
        value="{{ $systemStats['total_invoices'] }}" 
        icon="bi-receipt-cutoff" 
        color="info"
        subtitle="Paid invoices generated"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Active Businesses" 
        value="{{ $tenantsByStatus['active'] }}" 
        icon="bi-building-check" 
        color="warning"
        subtitle="Out of {{ $systemStats['total_tenants'] }} total"
      />
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Tenants Breakdown by Status -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="mb-0 fw-bold">Businesses by Subscription Status</h6>
        </div>
        <div class="card-body">
          @php
            $totalT = max(1, $systemStats['total_tenants']);
            $pctActive = round(($tenantsByStatus['active'] / $totalT) * 100);
            $pctSuspended = round(($tenantsByStatus['suspended'] / $totalT) * 100);
            $pctExpired = round(($tenantsByStatus['expired'] / $totalT) * 100);
          @endphp

          <div class="mb-4">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
              <span>{{ $tenantsByStatus['active'] }} ({{ $pctActive }}%)</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pctActive }}%"></div>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span class="text-danger"><i class="bi bi-slash-circle-fill me-1"></i> Suspended</span>
              <span>{{ $tenantsByStatus['suspended'] }} ({{ $pctSuspended }}%)</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $pctSuspended }}%"></div>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Expired / Grace</span>
              <span>{{ $tenantsByStatus['expired'] }} ({{ $pctExpired }}%)</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $pctExpired }}%"></div>
            </div>
          </div>

          <div class="border-top pt-3 mt-4 text-muted small d-flex justify-content-between">
            <span>Total Registered Businesses:</span>
            <span class="fw-bold text-dark">{{ $systemStats['total_tenants'] }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tenants Breakdown by Subscription Plan -->
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="mb-0 fw-bold">Plan Distribution & Estimated MRR</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Plan</th>
                <th>Subscribers</th>
                <th>Monthly Price</th>
                <th class="text-end pe-3">Est. MRR</th>
              </tr>
            </thead>
            <tbody>
              @php $estTotalMRR = 0; @endphp
              @forelse($tenantsByPlan as $plan)
                @php
                  $mrr = $plan->tenants_count * $plan->price_monthly;
                  $estTotalMRR += $mrr;
                @endphp
                <tr>
                  <td class="ps-3">
                    <span class="fw-semibold text-dark">{{ $plan->name }}</span>
                    @if($plan->is_popular)
                      <span class="badge bg-warning text-dark ms-1">Popular</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ $plan->tenants_count }} tenants</span>
                  </td>
                  <td>PKR {{ number_format($plan->price_monthly, 0) }}</td>
                  <td class="text-end pe-3 fw-bold text-primary">
                    PKR {{ number_format($mrr, 2) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-3 text-muted">No plans configured.</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="3" class="ps-3">Total Estimated MRR:</th>
                <th class="text-end pe-3 text-success fs-6">PKR {{ number_format($estTotalMRR, 2) }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Platform Infrastructure Summary -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
      <h6 class="mb-0 fw-bold">System Infrastructure & Data Aggregates</h6>
    </div>
    <div class="card-body">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3 border-end">
          <div class="fs-3 fw-bold text-primary">{{ $systemStats['total_tenants'] }}</div>
          <div class="text-muted small">Total Businesses</div>
        </div>
        <div class="col-6 col-md-3 border-end">
          <div class="fs-3 fw-bold text-success">{{ $systemStats['total_branches'] }}</div>
          <div class="text-muted small">Shop Branches / Outlets</div>
        </div>
        <div class="col-6 col-md-3 border-end">
          <div class="fs-3 fw-bold text-info">{{ $systemStats['total_users'] }}</div>
          <div class="text-muted small">User Accounts</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="fs-3 fw-bold text-secondary">{{ $systemStats['total_invoices'] }}</div>
          <div class="text-muted small">Subscription Invoices</div>
        </div>
      </div>
    </div>
  </div>
@endsection

