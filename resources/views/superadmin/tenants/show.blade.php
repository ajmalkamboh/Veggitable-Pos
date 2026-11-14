@extends('layouts.admin')

@section('title', 'Manage Business: ' . $tenant->name)

@section('content')
  <x-breadcrumb 
    :title="$tenant->name" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Businesses' => route('superadmin.tenants.index'),
      $tenant->slug => '#'
    ]"
  >
    <x-slot name="actions">
      <!-- Status Toggle Form -->
      <form method="POST" action="{{ route('superadmin.tenants.toggle-status', $tenant->id) }}" class="d-inline" onsubmit="return confirm('Change status for this tenant?');">
        @csrf
        @if($tenant->status->isActive())
          <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-slash-circle"></i>
            <span>Suspend Business</span>
          </button>
        @else
          <button type="submit" class="btn btn-success btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-check-circle"></i>
            <span>Activate Business</span>
          </button>
        @endif
      </form>

      <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#changePlanModal">
        <i class="bi bi-arrow-repeat"></i>
        <span>Change Plan</span>
      </button>

      <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#extendSubModal">
        <i class="bi bi-calendar-plus"></i>
        <span>Extend / Renew</span>
      </button>

      <a href="{{ route('superadmin.tenants.edit', $tenant->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit Info
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Usage & Key Metrics Row -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Current Status" 
        value="{{ $tenant->status->label() }}" 
        icon="bi-shield-check" 
        :color="$tenant->status->isActive() ? 'success' : 'danger'"
        subtitle="Tenant ID: #{{ $tenant->id }}"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Active Plan" 
        value="{{ $tenant->plan?->name ?? 'Free Trial' }}" 
        icon="bi-credit-card" 
        color="primary"
        subtitle="{{ $tenant->currentSubscription?->daysRemaining() ?? 0 }} days remaining"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Branches Deployed" 
        value="{{ $usage['branches_count'] }} / {{ $tenant->plan?->max_branches == -1 ? 'Unlimited' : ($tenant->plan?->max_branches ?? 1) }}" 
        icon="bi-geo-alt" 
        color="info"
        subtitle="Active Outlets"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Users / Staff" 
        value="{{ $usage['users_count'] }} / {{ $tenant->plan?->max_users == -1 ? 'Unlimited' : ($tenant->plan?->max_users ?? 2) }}" 
        icon="bi-people" 
        color="warning"
        subtitle="Rs. {{ number_format($usage['total_invoiced'], 0) }} Invoiced"
      />
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Business Profile & Details -->
    <div class="col-lg-6">
      <x-card title="Business Information" subtitle="Company registration & contact info">
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Business Name:</div>
          <div class="col-sm-8 fw-bold">{{ $tenant->name }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Slug / URL Key:</div>
          <div class="col-sm-8"><code>{{ $tenant->slug }}</code></div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">City / Region:</div>
          <div class="col-sm-8">{{ $tenant->city ?? 'Not specified' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Address:</div>
          <div class="col-sm-8">{{ $tenant->address ?? 'Not specified' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Phone:</div>
          <div class="col-sm-8">{{ $tenant->phone ?? 'Not specified' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Email:</div>
          <div class="col-sm-8">{{ $tenant->email ?? 'Not specified' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Currency / Timezone:</div>
          <div class="col-sm-8"><code>{{ $tenant->currency }}</code> | {{ $tenant->timezone }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Default Language:</div>
          <div class="col-sm-8"><span class="badge bg-light text-dark border">{{ strtoupper($tenant->default_locale) }}</span></div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Created Date:</div>
          <div class="col-sm-8 small text-muted">{{ $tenant->created_at->format('M d, Y H:i') }}</div>
        </div>
      </x-card>

      <!-- Owner Account -->
      <x-card title="Business Owner Account" subtitle="Primary merchant credentials">
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Owner Name:</div>
          <div class="col-sm-8 fw-bold">{{ $tenant->owner?->name ?? 'No Owner Assigned' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Email Address:</div>
          <div class="col-sm-8">{{ $tenant->owner?->email ?? 'N/A' }}</div>
        </div>
        <div class="row mb-2">
          <div class="col-sm-4 text-muted small">Account Status:</div>
          <div class="col-sm-8">
            <span class="badge bg-success-subtle text-success">{{ $tenant->owner?->status ?? 'active' }}</span>
          </div>
        </div>
      </x-card>
    </div>

    <!-- Branches & Subscription Invoices -->
    <div class="col-lg-6">
      <!-- Branches List -->
      <x-card title="Shop Branches / Outlets" :subtitle="$tenant->branches->count() . ' configured outlets'">
        <ul class="list-group list-group-flush small">
          @forelse($tenant->branches as $b)
            <li class="list-group-item d-flex align-items-center justify-content-between px-0">
              <div>
                <span class="fw-bold">{{ $b->name }}</span>
                <span class="text-muted d-block">Code: <code>{{ $b->code }}</code> | City: {{ $b->city ?? $tenant->city }}</span>
              </div>
              <div class="d-flex align-items-center gap-2">
                @if($b->is_headquarters)
                  <span class="badge bg-primary">Headquarters</span>
                @endif
                <span class="badge {{ $b->is_active ? 'bg-success' : 'bg-secondary' }}">
                  {{ $b->is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </li>
          @empty
            <li class="list-group-item px-0 text-muted">No branches found.</li>
          @endforelse
        </ul>
      </x-card>

      <!-- Recent Invoices -->
      <x-card title="Subscription Billing History" subtitle="Recent payments and renewals">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tenant->invoices as $inv)
                <tr>
                  <td><code>{{ $inv->invoice_number }}</code></td>
                  <td><strong>Rs. {{ number_format($inv->amount, 0) }}</strong></td>
                  <td><span class="text-capitalize">{{ $inv->payment_method }}</span></td>
                  <td><span class="badge {{ $inv->payment_status->badgeClass() }}">{{ $inv->payment_status->label() }}</span></td>
                  <td class="small text-muted">{{ $inv->created_at->format('M d, Y') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-3 text-muted">No billing history recorded yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </x-card>
    </div>
  </div>

  <!-- Change Plan Modal -->
  <x-modal id="changePlanModal" title="Change Subscription Plan">
    <form method="POST" action="{{ route('superadmin.tenants.change-plan', $tenant->id) }}">
      @csrf

      <div class="mb-3">
        <label for="plan_id" class="form-label small fw-medium">Select Plan</label>
        <select name="plan_id" id="plan_id" class="form-select" required>
          @foreach($availablePlans as $p)
            <option value="{{ $p->id }}" {{ $tenant->plan_id == $p->id ? 'selected' : '' }}>
              {{ $p->name }} - Rs. {{ number_format($p->price_monthly, 0) }}/mo ({{ $p->max_branches == -1 ? 'Unlimited' : $p->max_branches }} branches)
            </option>
          @endforeach
        </select>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <label for="billing_cycle" class="form-label small fw-medium">Billing Cycle</label>
          <select name="billing_cycle" id="billing_cycle" class="form-select" required>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div class="col-md-6">
          <label for="amount_paid" class="form-label small fw-medium">Amount Received (Rs.)</label>
          <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" placeholder="0.00" value="0.00">
        </div>
      </div>

      <div class="mb-3">
        <label for="payment_method" class="form-label small fw-medium">Payment Method</label>
        <select name="payment_method" id="payment_method" class="form-select">
          <option value="manual">Manual / Cash</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="jazzcash">JazzCash</option>
          <option value="easypaisa">Easypaisa</option>
        </select>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Assign Plan</button>
      </div>
    </form>
  </x-modal>

  <!-- Extend Subscription Modal -->
  <x-modal id="extendSubModal" title="Extend / Renew Subscription">
    <form method="POST" action="{{ route('superadmin.tenants.extend', $tenant->id) }}">
      @csrf

      <div class="mb-3">
        <label for="months" class="form-label small fw-medium">Extension Duration (Months)</label>
        <select name="months" id="months" class="form-select" required>
          <option value="1">1 Month</option>
          <option value="3">3 Months</option>
          <option value="6">6 Months</option>
          <option value="12" selected>12 Months (1 Year)</option>
        </select>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <label for="ext_amount" class="form-label small fw-medium">Amount Received (Rs.)</label>
          <input type="number" step="0.01" name="amount_paid" id="ext_amount" class="form-control" placeholder="0.00" value="0.00">
        </div>
        <div class="col-md-6">
          <label for="ext_method" class="form-label small fw-medium">Payment Method</label>
          <select name="payment_method" id="ext_method" class="form-select">
            <option value="manual">Manual / Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="jazzcash">JazzCash</option>
            <option value="easypaisa">Easypaisa</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label for="ext_notes" class="form-label small fw-medium">Notes / Receipt Reference</label>
        <textarea name="notes" id="ext_notes" rows="2" class="form-control" placeholder="Payment reference or notes..."></textarea>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Confirm Renewal</button>
      </div>
    </form>
  </x-modal>
@endsection

