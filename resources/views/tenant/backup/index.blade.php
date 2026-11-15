@extends('layouts.admin')

@section('title', __('Business Data Backup & Export'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-7">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Business Data Export & Portable Backup') }}</h4>
    <p class="text-muted small mb-0">{{ __('Export all isolated business records including produce master, suppliers, customers, inventory, sales, and financial ledgers') }}</p>
  </div>
  <div class="col-md-5 text-md-end">
    <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
    </a>
  </div>
</div>

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

<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-dark">
          <i class="bi bi-cloud-arrow-down text-primary me-2"></i>{{ __('Generate Business Data Archive') }}
        </h5>
      </div>
      <div class="card-body">
        <p class="text-muted small">
          {{ __('Export a self-contained, structured JSON backup of your business. This archive contains all produce lots, prices, inventory stocks, sales receipts, customer credit accounts, mandi aarthi payables, expenses, and double-entry accounting ledgers.') }}
        </p>

        <div class="alert alert-info py-2 small mb-4">
          <i class="bi bi-shield-check me-1"></i>
          {{ __('Strict Data Isolation: Only records belonging to') }} <strong>{{ $tenant->name }}</strong> {{ __('are exported. Data from other tenants is completely inaccessible.') }}
        </div>

        <form action="{{ route('tenant.backup.export') }}" method="POST">
          @csrf
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="compressSwitch" name="compress" value="1">
              <label class="form-check-label fw-semibold" for="compressSwitch">
                {{ __('Gzip Compress Archive (.json.gz)') }}
              </label>
              <div class="text-muted small">{{ __('Reduces file size by up to 85% for fast download and archiving.') }}</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-download"></i>
            <span>{{ __('Export & Download My Business Data') }}</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-dark">
          <i class="bi bi-folder-check text-success me-2"></i>{{ __('Available Business Exports') }}
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Archive Name') }}</th>
                <th>{{ __('Size') }}</th>
                <th>{{ __('Date') }}</th>
                <th class="text-end">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tenantBackups as $b)
                <tr>
                  <td>
                    <div class="fw-semibold small font-monospace">{{ $b['filename'] }}</div>
                  </td>
                  <td>{{ $b['formatted_size'] }}</td>
                  <td class="small">{{ \Carbon\Carbon::parse($b['created_at'])->format('d M, h:i A') }}</td>
                  <td class="text-end">
                    <a href="{{ route('superadmin.backups.download', $b['filename']) }}" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-download"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted small">
                    <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                    {{ __('No exports generated yet. Click the button to create your first export.') }}
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
