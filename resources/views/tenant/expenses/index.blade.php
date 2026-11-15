@extends('layouts.admin')

@section('title', __('Expense & Petty Cash Management'))

@section('content')
  <x-breadcrumb 
    :title="__('Expenses & Petty Cash')" 
    :items="[
      __('Dashboard') => route('tenant.records.index'),
      __('Expenses') => route('tenant.expenses.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.expenses.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('Record New Expense') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
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
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('This Month Expenses') }}</span>
              <h3 class="mb-0 fw-bold text-danger mt-1">Rs. {{ number_format($monthExpenses, 2) }}</h3>
              <small class="text-muted fs-11">{{ __('Shop overheads & mandi logistics') }}</small>
            </div>
            <div class="avatar-sm bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-wallet2 fs-4"></i>
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
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today\'s Expenses') }}</span>
              <h3 class="mb-0 fw-bold text-warning mt-1">Rs. {{ number_format($todayExpenses, 2) }}</h3>
              <small class="text-muted fs-11">{{ __('Daily tea, carriage & petty cash') }}</small>
            </div>
            <div class="avatar-sm bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-cup-hot fs-4"></i>
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
              <h3 class="mb-0 fw-bold text-info mt-1">{{ $pendingApprovals }}</h3>
              <small class="text-muted fs-11">{{ __('Awaiting manager authorization') }}</small>
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
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Available Cash & Bank') }}</span>
              <h3 class="mb-0 fw-bold text-success mt-1">Rs. {{ number_format($totalCashBalance, 2) }}</h3>
              <small class="text-muted fs-11">{{ __('Current drawer & bank reserves') }}</small>
            </div>
            <div class="avatar-sm bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-cash-stack fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.expenses.index') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search voucher #, payee, or notes...') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <select name="category_id" class="form-select form-select-sm">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }} @if($cat->name_ur) ({{ $cat->name_ur }}) @endif
              </option>
            @endforeach
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
          <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>{{ __('Pending Approval') }}</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.expenses.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Expenses Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-receipt me-2 text-primary"></i>{{ __('Expense Vouchers') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Voucher #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Paid To / Payee') }}</th>
            <th>{{ __('Payment Mode') }}</th>
            <th class="text-end">{{ __('Amount') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th>{{ __('Recorded By') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($expenses as $exp)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.expenses.show', $exp->id) }}" class="text-decoration-none text-primary">
                  {{ $exp->expense_no }}
                </a>
              </td>
              <td>{{ $exp->date->format('d M, Y') }}</td>
              <td><span class="badge bg-light text-dark border">{{ $exp->branch->name }}</span></td>
              <td>
                <span class="badge bg-primary-subtle text-primary border">
                  {{ $exp->category->name }}
                </span>
                @if($exp->category->name_ur)
                  <span class="small text-muted d-block">{{ $exp->category->name_ur }}</span>
                @endif
              </td>
              <td>
                <span class="fw-semibold">{{ $exp->payee_name ?? '-' }}</span>
                @if($exp->reference_no)
                  <small class="text-muted d-block">Ref: {{ $exp->reference_no }}</small>
                @endif
              </td>
              <td>
                <span class="small">{{ $exp->payment_method_label }}</span>
                @if($exp->financialAccount)
                  <small class="text-muted d-block">{{ $exp->financialAccount->name }}</small>
                @endif
              </td>
              <td class="text-end fw-bold text-danger">Rs. {{ number_format($exp->amount, 2) }}</td>
              <td class="text-center">
                <span class="badge {{ $exp->status_badge_class }}">
                  {{ ucfirst(str_replace('_', ' ', $exp->status)) }}
                </span>
              </td>
              <td><small class="text-muted">{{ $exp->creator?->name ?? 'System' }}</small></td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('tenant.expenses.show', $exp->id) }}" class="btn btn-outline-secondary" title="{{ __('View Details') }}">
                    <i class="bi bi-eye"></i>
                  </a>
                  @if($exp->status === 'pending_approval')
                    <form method="POST" action="{{ route('tenant.expenses.approve', $exp->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Approve this expense voucher?') }}');">
                      @csrf
                      <button type="submit" class="btn btn-outline-success" title="{{ __('Approve') }}">
                        <i class="bi bi-check-lg"></i>
                      </button>
                    </form>
                  @endif
                  <form method="POST" action="{{ route('tenant.expenses.destroy', $exp->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Cancel this expense and refund balance?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" title="{{ __('Cancel / Delete') }}">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                {{ __('No expense vouchers found.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($expenses->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $expenses->withQueryString()->links() }}
      </div>
    @endif
  </div>
@endsection

