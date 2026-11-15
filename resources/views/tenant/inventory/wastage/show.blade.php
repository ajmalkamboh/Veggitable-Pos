@extends('layouts.admin')

@section('title', __('Wastage Voucher: ') . $record->wastage_no)

@section('content')
  <x-breadcrumb 
    :title="$record->wastage_no" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Wastage') => route('tenant.inventory.wastage.index'),
      $record->wastage_no => route('tenant.inventory.wastage.show', $record->id)
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.wastage.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to List') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.wastage.create') }}" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Wastage Voucher') }}</span>
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
  @if($record->status === 'pending_approval')
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center p-3 mb-4">
      <div>
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning"></i>
        <span class="fw-bold">{{ __('This wastage voucher is pending review.') }}</span>
        <span class="text-muted small ms-2">{{ __('Inventory stock has not yet been deducted.') }}</span>
      </div>
      <div class="d-flex gap-2">
        <form method="POST" action="{{ route('tenant.inventory.wastage.approve', $record->id) }}" onsubmit="return confirm('{{ __('Approve voucher and deduct :qty kg stock?', ['qty' => $record->total_quantity]) }}');">
          @csrf
          <button type="submit" class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-check-circle me-1"></i> {{ __('Approve & Deduct Stock') }}
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
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-receipt me-2 text-danger"></i>{{ __('Wastage Voucher Items') }}
          </h6>
          <span class="badge {{ $record->status_badge_class }} fs-12 px-3 py-2">
            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Produce Item') }}</th>
                <th>{{ __('Lot / Batch') }}</th>
                <th class="text-end">{{ __('Weight / Qty') }}</th>
                <th class="text-end">{{ __('Cost Basis') }}</th>
                <th class="text-end">{{ __('Loss Value') }}</th>
                <th>{{ __('Disposal') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($record->items as $item)
                <tr>
                  <td class="ps-3">
                    <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                    @if($item->product->name_ur)
                      <div class="text-muted small">{{ $item->product->name_ur }}</div>
                    @endif
                    <small class="text-secondary font-monospace">{{ $item->product->code }}</small>
                  </td>
                  <td>
                    @if($item->batch)
                      <span class="badge bg-light text-dark border font-monospace">{{ $item->batch->lot_number }}</span>
                    @else
                      <span class="text-muted small">{{ __('FIFO Auto Allocation') }}</span>
                    @endif
                  </td>
                  <td class="text-end fw-bold">{{ number_format($item->quantity, 2) }} {{ $item->unit_code }}</td>
                  <td class="text-end">Rs. {{ number_format($item->unit_cost, 2) }}</td>
                  <td class="text-end fw-bold text-danger">Rs. {{ number_format($item->total_cost, 2) }}</td>
                  <td>
                    <span class="badge bg-light text-secondary border small">
                      {{ ucfirst($item->disposal_method) }}
                    </span>
                    @if($item->notes)
                      <div class="small text-muted">{{ $item->notes }}</div>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr>
                <td colspan="2" class="ps-3">{{ __('Totals') }}:</td>
                <td class="text-end">{{ number_format($record->total_quantity, 2) }} kg</td>
                <td class="text-end">-</td>
                <td class="text-end text-danger fs-6">Rs. {{ number_format($record->total_loss_cost, 2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Sidebar Summary Details -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-info-circle me-2 text-primary"></i>{{ __('Voucher Details') }}
          </h6>
        </div>
        <div class="card-body p-3">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Voucher #') }}:</span>
              <span class="fw-bold font-monospace">{{ $record->wastage_no }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Inspection Date') }}:</span>
              <span class="fw-semibold">{{ $record->date->format('d M, Y') }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Branch Outlet') }}:</span>
              <span class="fw-semibold">{{ $record->branch->name }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Primary Reason') }}:</span>
              <span class="fw-bold text-danger">{{ $record->reason_label }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Recorded By') }}:</span>
              <span class="fw-semibold">{{ $record->creator?->name ?? 'System' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Created At') }}:</span>
              <span>{{ $record->created_at->format('d M, Y H:i') }}</span>
            </li>

            @if($record->approved_by)
              <li class="list-group-item d-flex justify-content-between px-0 py-2 bg-light p-2 rounded mt-2">
                <span class="text-muted">{{ __('Reviewed By') }}:</span>
                <span class="fw-bold text-success">{{ $record->approver?->name }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">{{ __('Reviewed At') }}:</span>
                <span>{{ $record->approved_at?->format('d M, Y H:i') }}</span>
              </li>
            @endif

            @if($record->rejection_reason)
              <li class="list-group-item px-0 py-2 text-danger">
                <strong>{{ __('Rejection Reason') }}:</strong>
                <div>{{ $record->rejection_reason }}</div>
              </li>
            @endif
          </ul>

          @if($record->notes)
            <div class="mt-3 p-2 bg-light rounded small">
              <span class="fw-bold text-muted d-block">{{ __('Notes') }}:</span>
              {{ $record->notes }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Rejection Modal -->
  @if($record->status === 'pending_approval')
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant.inventory.wastage.reject', $record->id) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-danger">{{ __('Reject Wastage Voucher') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">{{ __('Please provide a reason for rejecting this wastage record. Stock will NOT be deducted.') }}</p>
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Rejection Reason') }}</label>
              <textarea name="rejection_reason" rows="3" class="form-control" required placeholder="{{ __('e.g., Stock count was miscalculated...') }}"></textarea>
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

