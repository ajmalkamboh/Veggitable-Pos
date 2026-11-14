@extends('layouts.admin')

@section('title', 'Tenant Data Isolation & Management')

@section('content')
  @inject('tenantContext', 'App\Core\MultiTenancy\TenantContext')

  <x-breadcrumb 
    title="Tenant Records & Data Isolation" 
    :items="[
      'Multi-Tenancy' => '#',
      'Tenant Records' => '#'
    ]"
  >
    <x-slot name="actions">
      @if($tenantContext->hasTenant())
        <div class="dropdown">
          <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-geo-alt"></i>
            <span>Branch: {{ $tenantContext->getBranch()?->name ?? 'None' }}</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            @foreach($branches as $b)
              <li>
                <form method="POST" action="{{ route('tenant.branch.switch', $b->id) }}">
                  @csrf
                  <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between {{ $tenantContext->getBranchId() === $b->id ? 'active' : '' }}">
                    <span>{{ $b->name }} ({{ $b->code }})</span>
                    @if($b->is_headquarters)
                      <span class="badge bg-secondary ms-2">HQ</span>
                    @endif
                  </button>
                </form>
              </li>
            @endforeach
          </ul>
        </div>
      @endif

      <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createRecordModal">
        <i class="bi bi-plus-circle"></i>
        <span>Add Tenant Record</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- Active Tenant Context Info Banner -->
  <div class="card bg-light border-0 shadow-sm mb-4">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary text-white p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
          <i class="bi bi-shop fs-3"></i>
        </div>
        <div>
          <h5 class="fw-bold mb-0">{{ $tenantContext->getTenant()?->name ?? 'No Tenant Selected' }}</h5>
          <span class="small text-muted">
            Tenant ID: <code>#{{ $tenantContext->getTenantId() ?? 'N/A' }}</code> |
            Slug: <code>{{ $tenantContext->getTenant()?->slug ?? 'N/A' }}</code> |
            Currency: <code>{{ $tenantContext->getTenant()?->currency ?? 'PKR' }}</code> |
            Status: <span class="badge {{ $tenantContext->getTenant()?->status->badgeClass() ?? 'bg-secondary' }}">{{ $tenantContext->getTenant()?->status->label() ?? 'None' }}</span>
          </span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('tenant.register.create') }}" class="btn btn-outline-success btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Register New Business
        </a>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <x-card title="Isolated Tenant Records" subtitle="Guaranteed scoped to active tenant context">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Branch</th>
                <th>Amount</th>
                <th>Created</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $rec)
                <tr>
                  <td><code>{{ $rec->code }}</code></td>
                  <td class="fw-semibold">{{ $rec->name }}</td>
                  <td>
                    @if($rec->branch)
                      <span class="badge bg-light text-dark border">{{ $rec->branch->name }}</span>
                    @else
                      <span class="text-muted small">Global</span>
                    @endif
                  </td>
                  <td><strong>Rs. {{ number_format($rec->amount, 2) }}</strong></td>
                  <td class="small text-muted">{{ $rec->created_at->format('M d, Y') }}</td>
                  <td class="text-end">
                    <a href="{{ route('tenant.records.show', $rec->id) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-eye"></i> View
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    No records found for this business. Click "Add Tenant Record" to create one.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </x-card>
    </div>

    <div class="col-lg-4">
      <x-card title="Data Isolation Guarantee" subtitle="Enterprise Multi-Tenancy Architecture">
        <div class="p-3 bg-light rounded mb-3 small">
          <h6 class="fw-bold text-success mb-2"><i class="bi bi-shield-check me-1"></i> Strict Multi-Tenant Enforcement</h6>
          <p class="text-muted mb-2">Each tenant query automatically resolves through <code>TenantScope</code> at the Eloquent level.</p>
          <p class="text-muted mb-0">Cross-tenant queries via route parameter tampering or spoofed <code>tenant_id</code> form fields are rejected at the service and policy layers.</p>
        </div>

        <h6 class="fw-semibold small text-muted text-uppercase mb-2">Multi-Branch Setup</h6>
        <ul class="list-group list-group-flush small mb-3">
          @forelse($branches as $b)
            <li class="list-group-item d-flex align-items-center justify-content-between px-0">
              <div>
                <span class="fw-medium">{{ $b->name }}</span>
                <span class="text-muted d-block small">Code: {{ $b->code }}</span>
              </div>
              @if($b->is_headquarters)
                <span class="badge bg-primary">Headquarters</span>
              @endif
            </li>
          @empty
            <li class="list-group-item px-0 text-muted">No branches found.</li>
          @endforelse
        </ul>
      </x-card>
    </div>
  </div>

  <!-- Create Record Modal -->
  <x-modal id="createRecordModal" title="Create Tenant Record">
    <form method="POST" action="{{ route('tenant.records.store') }}">
      @csrf

      <x-form-input 
        name="name" 
        label="Record Name" 
        placeholder="e.g. Fresh Potato Shipment Lot 1" 
        required 
      />

      <div class="row g-2">
        <div class="col-md-6">
          <x-form-input 
            name="code" 
            label="Record Code" 
            placeholder="e.g. REC-101" 
            help="Unique per tenant" 
            required 
          />
        </div>
        <div class="col-md-6">
          <x-form-input 
            type="number" 
            step="0.01" 
            name="amount" 
            label="Amount (Rs.)" 
            placeholder="0.00" 
            required 
          />
        </div>
      </div>

      <div class="mb-3">
        <label for="branch_id" class="form-label small fw-medium">Branch Assignment</label>
        <select name="branch_id" id="branch_id" class="form-select">
          <option value="">-- Active Branch Default --</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label small fw-medium">Description</label>
        <textarea name="description" id="description" rows="3" class="form-control" placeholder="Optional notes..."></textarea>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Record</button>
      </div>
    </form>
  </x-modal>
@endsection
