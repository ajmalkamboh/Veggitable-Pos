@extends('layouts.admin')

@section('title', 'Subscription Payments & Invoices')

@section('content')
  <x-breadcrumb 
    title="Subscription Payments & Invoices" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Subscriptions' => '#',
      'Payments' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.subscriptions.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cash-coin"></i>
        <span>Record Manual Payment</span>
      </a>
      <a href="{{ route('superadmin.plans.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-credit-card"></i>
        <span>Manage Plans</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('superadmin.subscriptions.payments') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input 
              type="text" 
              name="search" 
              value="{{ request('search') }}" 
              class="form-control form-control-sm border-start-0" 
              placeholder="Search invoice #, tenant name or reference..."
            >
          </div>
        </div>
        <div class="col-6 col-md-3">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Payment Statuses</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
        </div>
        @if(request()->hasAny(['search', 'status']))
          <div class="col-12 col-md-2">
            <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  <!-- Invoices Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">All Platform Transactions</h6>
      <span class="badge bg-light text-dark">{{ $invoices->total() }} Total Invoices</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Invoice #</th>
            <th>Business / Tenant</th>
            <th>Plan</th>
            <th>Amount</th>
            <th>Method & Ref</th>
            <th>Status</th>
            <th>Paid Date</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td class="ps-3 fw-semibold font-monospace">
                <a href="{{ route('superadmin.subscriptions.show', $inv->id) }}" class="text-decoration-none">
                  {{ $inv->invoice_number }}
                </a>
              </td>
              <td>
                <div class="fw-semibold">{{ $inv->tenant?->name ?? 'N/A' }}</div>
                <small class="text-muted">{{ $inv->tenant?->slug }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  {{ $inv->plan?->name ?? 'Standard' }}
                </span>
              </td>
              <td class="fw-bold text-success">
                {{ $inv->currency }} {{ number_format($inv->amount, 2) }}
              </td>
              <td>
                <div class="small fw-semibold text-capitalize">{{ str_replace('_', ' ', $inv->payment_method) }}</div>
                @if($inv->transaction_reference)
                  <small class="text-muted font-monospace">{{ $inv->transaction_reference }}</small>
                @endif
              </td>
              <td>
                @php
                  $statusBadge = match($inv->payment_status?->value ?? $inv->payment_status) {
                    'paid' => 'bg-success',
                    'pending' => 'bg-warning text-dark',
                    'failed' => 'bg-danger',
                    'refunded' => 'bg-secondary',
                    default => 'bg-info',
                  };
                @endphp
                <span class="badge {{ $statusBadge }} rounded-pill">
                  {{ ucfirst($inv->payment_status?->value ?? $inv->payment_status) }}
                </span>
              </td>
              <td class="small text-muted">
                {{ $inv->paid_at ? $inv->paid_at->format('M d, Y h:i A') : $inv->created_at->format('M d, Y') }}
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('superadmin.subscriptions.show', $inv->id) }}" class="btn btn-sm btn-outline-primary" title="View Invoice">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                No subscription invoices found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($invoices->hasPages())
      <div class="card-footer bg-white border-0 py-3">
        {{ $invoices->links() }}
      </div>
    @endif
  </div>
@endsection

