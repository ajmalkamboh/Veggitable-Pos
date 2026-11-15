@extends('layouts.admin')

@section('title', 'System Health & Production Readiness')

@section('content')
  <x-breadcrumb 
    title="System Health & Production Readiness" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'System Health' => '#'
    ]"
  >
    <x-slot name="actions">
      <div class="d-flex align-items-center gap-2">
        <form action="{{ route('superadmin.health.optimize') }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Optimize Caches</span>
          </button>
        </form>

        <form action="{{ route('superadmin.health.clear') }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-trash"></i>
            <span>Clear Caches</span>
          </button>
        </form>
      </div>
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

  <!-- Metrics Header -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle me-3">
            <i class="bi bi-check-circle-fill fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Readiness Score</div>
            <h4 class="mb-0 fw-bold">{{ $diagnostics['summary']['score_percent'] }}%</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
            <i class="bi bi-shield-check fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Passed Checks</div>
            <h4 class="mb-0 fw-bold">{{ $diagnostics['summary']['passed'] }} / {{ $diagnostics['summary']['total'] }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle me-3">
            <i class="bi bi-cpu fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">PHP Runtime</div>
            <h4 class="mb-0 fw-bold">v{{ explode('-', PHP_VERSION)[0] }}</h4>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle me-3">
            <i class="bi bi-memory fs-3"></i>
          </div>
          <div>
            <div class="text-muted small">Memory Limit</div>
            <h4 class="mb-0 fw-bold">{{ $diagnostics['server_specs']['memory_limit'] }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Diagnostic Checks -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0 fw-bold text-dark">
            <i class="bi bi-card-checklist text-primary me-2"></i>Production Launch Readiness Evaluation
          </h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Readiness Component</th>
                <th>Status</th>
                <th>Diagnostic Finding</th>
              </tr>
            </thead>
            <tbody>
              @foreach($diagnostics['checks'] as $key => $check)
                <tr>
                  <td class="fw-bold">{{ $check['name'] }}</td>
                  <td>
                    @if($check['status'] === 'pass')
                      <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check-circle-fill me-1"></i> PASS
                      </span>
                    @elseif($check['status'] === 'warn')
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> WARN
                      </span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-x-circle-fill me-1"></i> FAIL
                      </span>
                    @endif
                  </td>
                  <td class="small text-muted">{{ $check['detail'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column: Server Specifications & Launch Checklist -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-hdd-stack text-secondary me-2"></i>Server & Runtime Specifications
          </h6>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">PHP Version</span>
              <span class="fw-semibold">{{ $diagnostics['server_specs']['php_version'] }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Framework</span>
              <span class="fw-semibold">Laravel v{{ $diagnostics['server_specs']['laravel_version'] }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Operating System</span>
              <span class="fw-semibold">{{ $diagnostics['server_specs']['os'] }} ({{ $diagnostics['server_specs']['architecture'] }})</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Max Execution Time</span>
              <span class="fw-semibold">{{ $diagnostics['server_specs']['max_execution_time'] }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Active Caches</span>
              <span>
                @if($diagnostics['checks']['cache_and_optimization']['config_cached'])
                  <span class="badge bg-success">Config Cached</span>
                @else
                  <span class="badge bg-secondary">Config Live</span>
                @endif
              </span>
            </li>
          </ul>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-rocket-takeoff text-success me-2"></i>Commercial SaaS Launch Checklist
          </h6>
        </div>
        <div class="card-body small">
          <div class="d-flex align-items-start gap-2 mb-2">
            <i class="bi bi-check-square-fill text-success mt-1"></i>
            <div>
              <strong>Cron Scheduler Daemon:</strong>
              <code class="d-block text-dark mt-1 bg-light p-1 rounded font-monospace small">
                * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
              </code>
            </div>
          </div>

          <div class="d-flex align-items-start gap-2 mb-2">
            <i class="bi bi-check-square-fill text-success mt-1"></i>
            <div>
              <strong>Queue Worker Daemon:</strong>
              <div class="text-muted">Supervisor managed background queue workers.</div>
            </div>
          </div>

          <div class="d-flex align-items-start gap-2 mb-2">
            <i class="bi bi-check-square-fill text-success mt-1"></i>
            <div>
              <strong>SSL / TLS HTTPS:</strong>
              <div class="text-muted">Strict HTTPS transport security enforced.</div>
            </div>
          </div>

          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-check-square-fill text-success mt-1"></i>
            <div>
              <strong>Automated Daily Backups:</strong>
              <div class="text-muted">Nightly Gzip dumps with 30-day retention.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

