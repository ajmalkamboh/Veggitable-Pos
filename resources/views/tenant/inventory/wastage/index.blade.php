@extends('layouts.admin')

@section('title', __('Produce Wastage, Damage & Spoilage Loss'))

@section('content')
  <x-breadcrumb 
    :title="__('Wastage & Spoilage Loss')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Wastage Records') => route('tenant.inventory.wastage.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Inventory') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.regradings.index') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-shuffle"></i>
        <span>{{ __('Produce Regrading') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.wastage.create') }}" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-trash3-fill"></i>
        <span>{{ __('Record Spoilage / Wastage') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Metrics Header -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-danger border-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Month Wastage Loss (PKR)') }}</span>
              <h3 class="mb-0 fw-bold text-danger mt-1">Rs. {{ number_format($totalLossThisMonth, 2) }}</h3>
              <small class="text-muted fs-11">{{ __('Cost basis loss to business') }}</small>
            </div>
            <div class="avatar-sm bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-currency-dollar fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-warning border-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Month Weight Lost') }}</span>
              <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($totalQtyThisMonth, 2) }} kg</h3>
              <small class="text-muted fs-11">{{ __('Spoilage & drying shrinkage') }}</small>
            </div>
            <div class="avatar-sm bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-trash3 fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-info border-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Pending Approvals') }}</span>
              <h3 class="mb-0 fw-bold text-info mt-1">{{ $pendingApprovalsCount }}</h3>
              <small class="text-muted fs-11">{{ __('Awaiting manager review') }}</small>
            </div>
            <div class="avatar-sm bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-hourglass-split fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-success border-4">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Total Vouchers') }}</span>
              <h3 class="mb-0 fw-bold text-dark mt-1">{{ $records->total() }}</h3>
              <small class="text-muted fs-11">{{ __('Wastage audit events') }}</small>
            </div>
            <div class="avatar-sm bg-light text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-clipboard2-x fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.inventory.wastage.index') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search voucher # or notes...') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>{{ __('Pending Approval') }}</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="branch_id" class="form-select form-select-sm">
            <option value="">{{ __('All Branches') }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="reason" class="form-select form-select-sm">
            <option value="">{{ __('All Reasons') }}</option>
            <option value="spoilage" {{ request('reason') === 'spoilage' ? 'selected' : '' }}>{{ __('Rotten / Spoilage') }}</option>
            <option value="drying_shrinkage" {{ request('reason') === 'drying_shrinkage' ? 'selected' : '' }}>{{ __('Drying Shrinkage') }}</option>
            <option value="handling_damage" {{ request('reason') === 'handling_damage' ? 'selected' : '' }}>{{ __('Handling Damage') }}</option>
            <option value="pest_damage" {{ request('reason') === 'pest_damage' ? 'selected' : '' }}>{{ __('Pest Damage') }}</option>
            <option value="trimming_cleaning" {{ request('reason') === 'trimming_cleaning' ? 'selected' : '' }}>{{ __('Trimming Waste') }}</option>
            <option value="expired" {{ request('reason') === 'expired' ? 'selected' : '' }}>{{ __('Expired Shelf Life') }}</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.inventory.wastage.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-trash3 me-2 text-danger"></i>{{ __('Wastage & Spoilage Vouchers') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Voucher #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Primary Reason') }}</th>
            <th class="text-center">{{ __('Items') }}</th>
            <th class="text-end">{{ __('Total Weight') }}</th>
            <th class="text-end">{{ __('Loss Value') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th>{{ __('Recorded By') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $rec)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.inventory.wastage.show', $rec->id) }}" class="text-decoration-none text-danger">
                  {{ $rec->wastage_no }}
                </a>
              </td>
              <td>{{ $rec->date->format('d M, Y') }}</td>
              <td><span class="badge bg-light text-dark border">{{ $rec->branch->name }}</span></td>
              <td>
                <span class="small fw-semibold text-secondary">{{ $rec->reason_label }}</span>
              </td>
              <td class="text-center">
                <span class="badge bg-secondary-subtle text-secondary">{{ $rec->total_items_count }}</span>
              </td>
              <td class="text-end fw-bold">{{ number_format($rec->total_quantity, 2) }} kg</td>
              <td class="text-end fw-bold text-danger">Rs. {{ number_format($rec->total_loss_cost, 2) }}</td>
              <td class="text-center">
                <span class="badge {{ $rec->status_badge_class }}">
                  {{ ucfirst(str_replace('_', ' ', $rec->status)) }}
                </span>
              </td>
              <td><small class="text-muted">{{ $rec->creator?->name ?? 'System' }}</small></td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tenant.inventory.wastage.show', $rec->id) }}" class="btn btn-outline-secondary" title="{{ __('View Voucher') }}">
                    <i class="bi bi-eye"></i>
                  </a>
                  @if($rec->status === 'pending_approval')
                    <form method="POST" action="{{ route('tenant.inventory.wastage.approve', $rec->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Approve this wastage voucher and deduct stock?') }}');">
                      @csrf
                      <button type="submit" class="btn btn-outline-success" title="{{ __('Approve & Deduct Stock') }}">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                {{ __('No wastage records found.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($records->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $records->withQueryString()->links() }}
      </div>
    @endif
  </div>
@endsection

