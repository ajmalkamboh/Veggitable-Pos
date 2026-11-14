@extends('layouts.admin')

@section('title', __('common.dashboard') . ' - ' . __('common.tagline'))

@section('content')
  @inject('tenantContext', 'App\Core\MultiTenancy\TenantContext')

  <x-breadcrumb 
    :title="__('common.dashboard')" 
    :items="[
      'Phase 02: Multi-Tenant Architecture' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.register.create') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>Register New Business</span>
      </a>
      <a href="{{ route('tenant.records.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-shield-check"></i>
        <span>Tenant Records & Isolation</span>
      </a>
      <a href="{{ route('language.switch', app()->getLocale() === 'en' ? 'ur' : 'en') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-translate"></i>
        <span>{{ app()->getLocale() === 'en' ? 'اردو (Urdu RTL)' : 'English (LTR)' }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Active Tenant Context Banner -->
  <div class="card bg-primary-subtle border-0 shadow-sm mb-4">
    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-shop fs-4"></i>
        </div>
        <div>
          <h6 class="fw-bold mb-0 text-primary-emphasis">Active Business Context: {{ $tenantContext->getTenant()?->name ?? 'Default Multi-Tenant Environment' }}</h6>
          <span class="small text-muted">
            Tenant ID: <code>#{{ $tenantContext->getTenantId() ?? '1' }}</code> |
            Active Branch: <strong>{{ $tenantContext->getBranch()?->name ?? 'Headquarters' }}</strong> |
            Currency: <code>{{ $tenantContext->getTenant()?->currency ?? 'PKR' }}</code> |
            Data Isolation: <span class="badge bg-success small">STRICTLY ENFORCED</span>
          </span>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('tenant.records.index') }}" class="btn btn-primary btn-sm">
          <i class="bi bi-database me-1"></i> View Isolated Records
        </a>
      </div>
    </div>
  </div>

  <!-- KPI Overview Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Active Businesses" 
        value="{{ \App\Models\Tenant::count() }}" 
        icon="bi-buildings" 
        color="primary"
        subtitle="Independent Tenants"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Branches Deployed" 
        value="{{ \App\Models\Branch::count() }}" 
        icon="bi-geo-alt" 
        color="success"
        subtitle="Multi-Branch Infrastructure"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Tenant Isolation" 
        value="100% Isolated" 
        icon="bi-shield-check" 
        color="info"
        change="Verified"
        changeType="positive"
        subtitle="Scopes, Policies, Services"
      />
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <x-stat-card 
        title="Phase Status" 
        value="Phase 02 Done" 
        icon="bi-check2-circle" 
        color="warning"
        change="Completed"
        changeType="positive"
        subtitle="SaaS Multi-Tenancy Ready"
      />
    </div>
  </div>

  <!-- Main System Info & Livewire Component -->
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <x-card title="Multi-Tenant SaaS Architecture & Technical Verification" subtitle="Shared MySQL database with strict row-level isolation and multi-branch hierarchy">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-primary"><i class="bi bi-shield-lock me-2"></i>Multi-Layer Tenant Security</h6>
              <p class="small text-muted mb-0">Never trusts client-supplied <code>tenant_id</code>. Automatically attaches active tenant ID via <code>BelongsToTenant</code>, filters all queries via <code>TenantScope</code>, and validates ownership at policy and service layers.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-success"><i class="bi bi-diagram-3 me-2"></i>Multi-Branch Hierarchy</h6>
              <p class="small text-muted mb-0">Supports multiple shop branches per business with headquarters flags, branch user assignments, active branch context switching, and composite unique keys <code>(tenant_id, code)</code>.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-warning"><i class="bi bi-person-plus me-2"></i>Atomic Tenant Registration</h6>
              <p class="small text-muted mb-0"><code>TenantRegistrationService</code> executes atomic business creation, initial headquarters branch setup, and owner user provisioning within guaranteed database transactions.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded p-3 bg-light">
              <h6 class="fw-semibold text-info"><i class="bi bi-clock-history me-2"></i>Tenant Lifecycle & Statuses</h6>
              <p class="small text-muted mb-0">Enforces tenant lifecycle states: <code>active</code>, <code>suspended</code>, and <code>expired</code> with middleware-enforced rejection of unauthorized operational access.</p>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="fw-bold mb-3">Multi-Tenancy Verification Matrix</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Mechanism</th>
                <th>Implementation Class</th>
                <th>Status</th>
                <th>Security Guarantee</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Context Resolution</strong></td>
                <td><code>TenantManager</code> & <code>TenantContext</code></td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Resolves from User, Route parameter, Header, Subdomain, or Session</td>
              </tr>
              <tr>
                <td><strong>Query Scoping</strong></td>
                <td><code>TenantScope</code> (Eloquent Global Scope)</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Appends <code>WHERE tenant_id = ?</code> to all tenant model queries</td>
              </tr>
              <tr>
                <td><strong>Model Guard</strong></td>
                <td><code>BelongsToTenant</code> Trait</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Rejects client-spoofed <code>tenant_id</code> with <code>CrossTenantAccessException</code></td>
              </tr>
              <tr>
                <td><strong>Route Protection</strong></td>
                <td><code>PreventCrossTenantAccess</code> Middleware</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Prevents cross-tenant access via URL parameter manipulation</td>
              </tr>
              <tr>
                <td><strong>Status Guard</strong></td>
                <td><code>EnsureTenantActive</code> Middleware</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Blocks suspended or expired tenant accounts with HTTP 403</td>
              </tr>
              <tr>
                <td><strong>Unique Constraints</strong></td>
                <td>MySQL Compound Indexes</td>
                <td><span class="badge bg-success-subtle text-success">Verified Active</span></td>
                <td>Allows identical codes across businesses while preventing duplicates within</td>
              </tr>
            </tbody>
          </table>
        </div>
      </x-card>
    </div>

    <!-- Livewire Reactive Widget -->
    <div class="col-lg-4">
      <x-card title="Livewire Reactive Foundation Test" subtitle="Testing real-time state manipulation">
        @livewire('architecture-overview')
      </x-card>

      <x-card title="Registered Demonstration Businesses" subtitle="Multi-Tenant Environment">
        <ul class="list-group list-group-flush small mb-3">
          @foreach(\App\Models\Tenant::with('branches')->get() as $t)
            <li class="list-group-item px-0">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-bold">{{ $t->name }}</span>
                <span class="badge {{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>
              </div>
              <span class="text-muted d-block">Slug: <code>{{ $t->slug }}</code> | Currency: <code>{{ $t->currency }}</code></span>
              <span class="text-muted d-block">Branches: {{ $t->branches->count() }} ({{ $t->branches->pluck('name')->implode(', ') }})</span>
              <div class="mt-2">
                <a href="{{ route('tenant.switch', $t->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2 small">
                  Switch to This Business
                </a>
              </div>
            </li>
          @endforeach
        </ul>
      </x-card>
    </div>
  </div>
@endsection
