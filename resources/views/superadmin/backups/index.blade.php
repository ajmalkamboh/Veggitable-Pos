@extends('layouts.admin')

@section('title', 'Database Backups & Security Audit')

@section('content')
  <x-breadcrumb 
    title="Platform Disaster Recovery & Security" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Backups & Security' => '#'
    ]"
  >
    <x-slot name="actions">
      <form action="{{ route('superadmin.backups.generate') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="compress" value="1">
        <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
          <i class="bi bi-cloud-arrow-down-fill"></i>
          <span>Create Instant Backup (.sql.gz)</span>
        </button>
      </form>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Key Metrics -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
            <i class="bi bi-archive-fill fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Total Backup Archives</div>
            <h4 class="mb-0 fw-bold">{{ count($backups) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle me-3">
            <i class="bi bi-shield-check fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Tenant Isolation Score</div>
            <h4 class="mb-0 fw-bold">{{ $securityMetrics['tenant_isolation']['isolation_integrity_percent'] }}%</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle me-3">
            <i class="bi bi-speedometer fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Active Rate Limiters</div>
            <h4 class="mb-0 fw-bold">5 Policies</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle me-3">
            <i class="bi bi-database-lock fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Audited Tenant Tables</div>
            <h4 class="mb-0 fw-bold">{{ $securityMetrics['tenant_isolation']['secure_tables_count'] }} / {{ $securityMetrics['tenant_isolation']['total_audited_tables'] }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <ul class="nav nav-pills mb-3" id="backupSecurityTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-semibold" id="backups-tab" data-bs-toggle="pill" data-bs-target="#backups-pane" type="button" role="tab">
        <i class="bi bi-hdd-network me-1"></i> Database Backups ({{ count($backups) }})
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="isolation-tab" data-bs-toggle="pill" data-bs-target="#isolation-pane" type="button" role="tab">
        <i class="bi bi-shield-lock me-1"></i> Multi-Tenant Isolation Audit
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="security-tab" data-bs-toggle="pill" data-bs-target="#security-pane" type="button" role="tab">
        <i class="bi bi-gear-wide-connected me-1"></i> Security Hardening Specs
      </button>
    </li>
  </ul>

  <div class="tab-content" id="backupSecurityContent">
    <!-- Backups Tab -->
    <div class="tab-pane fade show active" id="backups-pane" role="tabpanel">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold text-dark">
            <i class="bi bi-database-down text-primary me-2"></i>Platform Database Snapshot Archives
          </h5>
          <span class="badge bg-secondary">Retention: 30 Days</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Backup File</th>
                <th>Type</th>
                <th>Archive Size</th>
                <th>Created At</th>
                <th>SHA-256 Checksum</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($backups as $b)
                <tr>
                  <td>
                    <div class="fw-bold text-dark">{{ $b['filename'] }}</div>
                    @if(!empty($b['compressed']))
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Gzip Compressed</span>
                    @else
                      <span class="badge bg-secondary-subtle text-secondary border">Raw SQL</span>
                    @endif
                  </td>
                  <td>
                    @if(($b['type'] ?? '') === 'tenant_export')
                      <span class="badge bg-info">Tenant Export</span>
                    @else
                      <span class="badge bg-primary">Full Platform DB</span>
                    @endif
                  </td>
                  <td class="fw-semibold text-secondary">{{ $b['formatted_size'] }}</td>
                  <td>{{ \Carbon\Carbon::parse($b['created_at'])->format('d M Y, h:i A') }}</td>
                  <td>
                    <code class="small text-truncate d-inline-block" style="max-width: 200px;" title="{{ $b['sha256'] }}">
                      {{ $b['sha256'] }}
                    </code>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('superadmin.backups.download', $b['filename']) }}" class="btn btn-outline-primary" title="Download">
                        <i class="bi bi-download"></i> Download
                      </a>
                      <form action="{{ route('superadmin.backups.destroy', $b['filename']) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this backup archive?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-archive fs-1 d-block mb-2 text-secondary"></i>
                    No database backups found. Click <strong>Create Instant Backup</strong> above.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Multi-Tenant Isolation Audit Tab -->
    <div class="tab-pane fade" id="isolation-pane" role="tabpanel">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0 fw-bold text-dark">
            <i class="bi bi-check-all text-success me-2"></i>Multi-Tenant Data Partitioning & Isolation Audit
          </h5>
          <p class="text-muted small mb-0 mt-1">Verifies that every business table contains strict <code>tenant_id</code> constraints without orphan or cross-tenant leaks.</p>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Table Name</th>
                <th>Tenant Partitioning</th>
                <th>Total Records</th>
                <th>Null Tenant Rows</th>
                <th>Orphaned Rows</th>
                <th>Security Verdict</th>
              </tr>
            </thead>
            <tbody>
              @foreach($isolationAudit as $table => $audit)
                <tr>
                  <td class="fw-semibold"><code>{{ $table }}</code></td>
                  <td>
                    @if($audit['has_tenant_id'])
                      <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>tenant_id Column Present</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Missing tenant_id</span>
                    @endif
                  </td>
                  <td>{{ number_format($audit['total_records']) }}</td>
                  <td>
                    @if($audit['null_tenant_records'] === 0)
                      <span class="text-success fw-bold">0</span>
                    @else
                      <span class="badge bg-danger">{{ $audit['null_tenant_records'] }}</span>
                    @endif
                  </td>
                  <td>
                    @if($audit['orphaned_records'] === 0)
                      <span class="text-success fw-bold">0</span>
                    @else
                      <span class="badge bg-warning text-dark">{{ $audit['orphaned_records'] }}</span>
                    @endif
                  </td>
                  <td>
                    @if($audit['is_secure'])
                      <span class="badge bg-success"><i class="bi bi-shield-fill-check me-1"></i>Isolated & Secure</span>
                    @else
                      <span class="badge bg-danger"><i class="bi bi-shield-slash me-1"></i>Attention Required</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Security Specs Tab -->
    <div class="tab-pane fade" id="security-pane" role="tabpanel">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
              <h6 class="mb-0 fw-bold"><i class="bi bi-shield-shaded text-primary me-2"></i>Platform Security Configuration</h6>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>CSRF Form Protection</span>
                  <span class="badge bg-success">Active & Mandatory</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>Session Cookie HttpOnly</span>
                  <span class="badge bg-success">{{ $securityMetrics['session_httponly'] ? 'Enabled' : 'Disabled' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>SameSite Cookie Policy</span>
                  <span class="badge bg-info text-dark">{{ strtoupper($securityMetrics['session_same_site']) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>Password Hashing Algorithm</span>
                  <span class="badge bg-dark">{{ strtoupper($securityMetrics['password_hash_driver']) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span>Tenant Scoping Architecture</span>
                  <span class="badge bg-success">Global Scopes + Service Layer + DB Indexes</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
              <h6 class="mb-0 fw-bold"><i class="bi bi-speedometer2 text-danger me-2"></i>Rate Limiting & Anti-Abuse Policies</h6>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                @foreach($securityMetrics['rate_limiters'] as $limiter => $rule)
                  <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div>
                      <span class="fw-semibold font-monospace">{{ $limiter }}</span>
                      <small class="text-muted d-block">IP + User Context Throttling</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">{{ $rule }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
