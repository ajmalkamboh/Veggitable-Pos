@extends('layouts.admin')

@section('title', 'Login Activity & Audit Trail')

@section('content')
  <x-breadcrumb 
    title="Login Activity & Audit Trail" 
    :items="[
      'Tenant Operations' => '#',
      'Security' => '#',
      'Audit Trail' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-people"></i>
        <span>Manage Staff</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.audit.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-6">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input 
              type="text" 
              name="action" 
              value="{{ request('action') }}" 
              class="form-control form-control-sm border-start-0" 
              placeholder="Filter by action (e.g. auth.login, user.created)..."
            >
          </div>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
        </div>
        @if(request()->has('action'))
          <div class="col-6 col-md-2">
            <a href="{{ route('tenant.audit.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  <!-- Audit Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold">Recent Audit Logs</h6>
      <span class="badge bg-light text-dark">{{ $logs->total() }} Log Entries</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Timestamp</th>
            <th>User Account</th>
            <th>Action</th>
            <th>IP Address</th>
            <th>Details / Payload</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            <tr>
              <td class="ps-3 text-muted">
                {{ $log->created_at->format('Y-m-d H:i:s') }}
              </td>
              <td>
                <span class="font-sans fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</span>
                <span class="d-block text-muted" style="font-size: 0.75rem;">{{ $log->user?->email ?? 'N/A' }}</span>
              </td>
              <td>
                @php
                  $badgeClass = match(explode('.', $log->action)[0]) {
                    'auth' => 'bg-info-subtle text-info border border-info-subtle',
                    'user' => 'bg-primary-subtle text-primary border border-primary-subtle',
                    'role' => 'bg-warning-subtle text-warning border border-warning-subtle',
                    default => 'bg-secondary-subtle text-secondary border',
                  };
                @endphp
                <span class="badge {{ $badgeClass }} px-2 py-1">
                  {{ $log->action }}
                </span>
              </td>
              <td class="text-muted">
                {{ $log->ip_address ?? '127.0.0.1' }}
              </td>
              <td>
                @if($log->payload)
                  <pre class="mb-0 small bg-light p-1 rounded text-dark" style="max-width: 320px; white-space: pre-wrap;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted font-sans">
                <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                No audit trail logs recorded yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($logs->hasPages())
      <div class="card-footer bg-white border-0 py-3">
        {{ $logs->links() }}
      </div>
    @endif
  </div>
@endsection

