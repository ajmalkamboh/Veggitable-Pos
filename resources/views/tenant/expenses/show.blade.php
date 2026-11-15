@extends('layouts.admin')

@section('title', __('Expense Voucher: ') . $expense->expense_no)

@section('content')
  <x-breadcrumb 
    :title="$expense->expense_no" 
    :items="[
      __('Dashboard') => route('tenant.records.index'),
      __('Expenses') => route('tenant.expenses.index'),
      $expense->expense_no => route('tenant.expenses.show', $expense->id)
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.expenses.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to List') }}</span>
      </a>
      <a href="{{ route('tenant.expenses.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Expense') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Action Bar if Pending -->
  @if($expense->status === 'pending_approval')
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center p-3 mb-4">
      <div>
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning"></i>
        <span class="fw-bold">{{ __('This expense voucher is pending authorization.') }}</span>
        <span class="text-muted small ms-2">{{ __('Funds have not yet been deducted from the account.') }}</span>
      </div>
      <div class="d-flex gap-2">
        <form method="POST" action="{{ route('tenant.expenses.approve', $expense->id) }}" onsubmit="return confirm('{{ __('Approve voucher and deduct Rs. :amt?', ['amt' => number_format($expense->amount, 2)]) }}');">
          @csrf
          <button type="submit" class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-check-circle me-1"></i> {{ __('Approve & Deduct Funds') }}
          </button>
        </form>
        <button type="button" class="btn btn-outline-danger btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
          <i class="bi bi-x-circle me-1"></i> {{ __('Reject') }}
        </button>
      </div>
    </div>
  @endif

  <div class="row g-4">
    <!-- Main Voucher Info -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-wallet2 me-2 text-primary"></i>{{ __('Expense Voucher Information') }}
            </h6>
            <small class="text-muted font-monospace">{{ $expense->expense_no }}</small>
          </div>
          <span class="badge {{ $expense->status_badge_class }} fs-12 px-3 py-2">
            {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
          </span>
        </div>
        <div class="card-body p-4">
          <div class="p-3 bg-light rounded-3 mb-4 text-center">
            <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Expense Voucher Amount') }}</span>
            <h2 class="fw-bold text-danger mb-0 mt-1">Rs. {{ number_format($expense->amount, 2) }}</h2>
            <span class="badge bg-secondary-subtle text-secondary mt-2">{{ $expense->payment_method_label }}</span>
          </div>

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Expense Category') }}</label>
              <div class="fw-bold fs-6 text-primary">{{ $expense->category->name }}</div>
              @if($expense->category->name_ur)
                <div class="text-muted small">{{ $expense->category->name_ur }}</div>
              @endif
            </div>

            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Branch Outlet') }}</label>
              <div class="fw-semibold">{{ $expense->branch->name }}</div>
            </div>

            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Payee / Recipient') }}</label>
              <div class="fw-semibold">{{ $expense->payee_name ?? __('None specified') }}</div>
            </div>

            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Receipt / Bill #') }}</label>
              <div class="fw-semibold font-monospace">{{ $expense->reference_no ?? '-' }}</div>
            </div>

            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Paid From Account') }}</label>
              @if($expense->financialAccount)
                <div class="fw-bold text-dark">{{ $expense->financialAccount->name }} ({{ ucfirst($expense->financialAccount->type) }})</div>
              @else
                <div class="text-muted">{{ __('Direct Petty Cash') }}</div>
              @endif
            </div>

            <div class="col-sm-6">
              <label class="text-muted small d-block mb-1">{{ __('Recurring Schedule') }}</label>
              @if($expense->is_recurring)
                <span class="badge bg-info-subtle text-info fw-bold">
                  <i class="bi bi-arrow-repeat me-1"></i> {{ ucfirst($expense->recurring_frequency) }}
                </span>
              @else
                <span class="text-muted">{{ __('One-time Expense') }}</span>
              @endif
            </div>

            @if($expense->description)
              <div class="col-12 mt-3 pt-3 border-top">
                <label class="text-muted small d-block mb-1">{{ __('Description & Notes') }}</label>
                <div class="p-3 bg-light rounded text-secondary">{{ $expense->description }}</div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar Info & Attachment -->
    <div class="col-lg-4">
      <!-- Attachment Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-paperclip me-2 text-primary"></i>{{ __('Receipt Attachment') }}
          </h6>
        </div>
        <div class="card-body p-3 text-center">
          @if($expense->attachment_path)
            <div class="mb-3">
              <i class="bi bi-file-earmark-check fs-1 text-primary d-block mb-2"></i>
              <span class="fw-semibold small d-block text-truncate">{{ basename($expense->attachment_path) }}</span>
            </div>
            <a href="{{ asset('storage/' . $expense->attachment_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-download me-1"></i> {{ __('View / Download Document') }}
            </a>
          @else
            <div class="text-muted py-3">
              <i class="bi bi-file-earmark-x fs-2 d-block mb-1 text-secondary opacity-50"></i>
              <small>{{ __('No receipt photo attached.') }}</small>
            </div>
          @endif
        </div>
      </div>

      <!-- Audit Details Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-clock-history me-2 text-primary"></i>{{ __('Audit Trail') }}
          </h6>
        </div>
        <div class="card-body p-3">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Date') }}:</span>
              <span class="fw-semibold">{{ $expense->date->format('d M, Y') }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Logged By') }}:</span>
              <span class="fw-semibold">{{ $expense->creator?->name ?? 'System' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Logged At') }}:</span>
              <span>{{ $expense->created_at->format('d M, Y H:i') }}</span>
            </li>
            @if($expense->approved_by)
              <li class="list-group-item d-flex justify-content-between px-0 py-2 bg-light p-2 rounded mt-2">
                <span class="text-muted">{{ __('Authorized By') }}:</span>
                <span class="fw-bold text-success">{{ $expense->approver?->name }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">{{ __('Authorized At') }}:</span>
                <span>{{ $expense->approved_at?->format('d M, Y H:i') }}</span>
              </li>
            @endif
            @if($expense->rejection_reason)
              <li class="list-group-item px-0 py-2 text-danger">
                <strong>{{ __('Rejection Reason') }}:</strong>
                <div>{{ $expense->rejection_reason }}</div>
              </li>
            @endif
          </ul>

          <div class="mt-4 pt-3 border-top">
            <form method="POST" action="{{ route('tenant.expenses.destroy', $expense->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to cancel this expense voucher and refund the balance?') }}');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-trash me-1"></i> {{ __('Cancel / Delete Voucher') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Rejection Modal -->
  @if($expense->status === 'pending_approval')
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant.expenses.reject', $expense->id) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-danger">{{ __('Reject Expense Voucher') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">{{ __('Please provide a reason for rejecting this expense. Funds will NOT be deducted.') }}</p>
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Rejection Reason') }}</label>
              <textarea name="rejection_reason" rows="3" class="form-control" required placeholder="{{ __('e.g., Exceeds daily petty cash limit, invoice missing...') }}"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-danger btn-sm">{{ __('Confirm Rejection') }}</button>
          </div>
        </form>
      </div>
    </div>
  @endif
@endsection

