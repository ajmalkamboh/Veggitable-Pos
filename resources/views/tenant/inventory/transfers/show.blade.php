@extends('layouts.admin')

@section('title', __('Stock Transfer') . ' - ' . $stockTransfer->transfer_no)

@section('content')
  <x-breadcrumb 
    :title="$stockTransfer->transfer_no" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Transfers') => route('tenant.inventory.transfers.index'),
      $stockTransfer->transfer_no => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.transfers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Transfers') }}</span>
      </a>
      @if($stockTransfer->status === 'in_transit')
        <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#receiveModal">
          <i class="bi bi-check2-circle"></i>
          <span>{{ __('Receive at Destination') }}</span>
        </button>
      @endif
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

  <div class="row g-4 mb-4">
    <!-- Meta Info Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-info-circle me-2"></i>{{ __('Transfer Information') }}
          </h6>
        </div>
        <div class="card-body">
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>{{ __('Transfer Voucher #:') }}</strong> <span class="font-monospace fw-bold">{{ $stockTransfer->transfer_no }}</span></li>
            <li class="mb-2"><strong>{{ __('Transfer Date:') }}</strong> {{ $stockTransfer->transfer_date->format('d M, Y') }}</li>
            <li class="mb-2">
              <strong>{{ __('Status:') }}</strong>
              @if($stockTransfer->status === 'received')
                <span class="badge bg-success">{{ __('Received') }}</span>
              @elseif($stockTransfer->status === 'in_transit')
                <span class="badge bg-warning text-dark">{{ __('In Transit') }}</span>
              @else
                <span class="badge bg-secondary">{{ ucfirst($stockTransfer->status) }}</span>
              @endif
            </li>
            <li class="mb-2"><strong>{{ __('Created By:') }}</strong> {{ $stockTransfer->creator->name ?? 'System' }}</li>
            @if($stockTransfer->dispatched_at)
              <li class="mb-2"><strong>{{ __('Dispatched At:') }}</strong> {{ $stockTransfer->dispatched_at->format('d M, Y H:i') }} ({{ $stockTransfer->dispatcher->name ?? '' }})</li>
            @endif
            @if($stockTransfer->received_at)
              <li class="mb-2"><strong>{{ __('Received At:') }}</strong> {{ $stockTransfer->received_at->format('d M, Y H:i') }} ({{ $stockTransfer->receiver->name ?? '' }})</li>
            @endif
          </ul>
        </div>
      </div>
    </div>

    <!-- Logistics & Route Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-geo-alt me-2"></i>{{ __('Origin & Destination') }}
          </h6>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <span class="text-muted extra-small text-uppercase fw-semibold">{{ __('Dispatch Source (From):') }}</span>
            <div class="fw-bold fs-6 text-dark">{{ $stockTransfer->fromBranch->name ?? '-' }}</div>
          </div>
          <div class="mb-3">
            <span class="text-muted extra-small text-uppercase fw-semibold">{{ __('Receiving Destination (To):') }}</span>
            <div class="fw-bold fs-6 text-success">{{ $stockTransfer->toBranch->name ?? '-' }}</div>
          </div>
          @if($stockTransfer->notes)
            <div class="small text-muted border-top pt-2">
              <strong>{{ __('Notes:') }}</strong> {{ $stockTransfer->notes }}
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Transport & Driver Card -->
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-primary">
            <i class="bi bi-truck me-2"></i>{{ __('Driver & Vehicle') }}
          </h6>
        </div>
        <div class="card-body">
          <ul class="list-unstyled small mb-0">
            <li class="mb-2"><strong>{{ __('Vehicle #:') }}</strong> {{ $stockTransfer->vehicle_number ?? '-' }}</li>
            <li class="mb-2"><strong>{{ __('Driver Name:') }}</strong> {{ $stockTransfer->driver_name ?? '-' }}</li>
            <li class="mb-2"><strong>{{ __('Driver Phone:') }}</strong> {{ $stockTransfer->driver_phone ?? '-' }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Produce Items Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold text-dark">
        <i class="bi bi-box-seam me-2 text-success"></i>{{ __('Transferred Produce Items') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">#</th>
            <th>{{ __('Produce Item') }}</th>
            <th class="text-end">{{ __('Dispatched Qty') }}</th>
            <th class="text-end">{{ __('Received Qty') }}</th>
            <th class="text-end">{{ __('Unit Cost') }}</th>
            <th class="text-end pe-3">{{ __('Transfer Value') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($stockTransfer->items as $idx => $item)
            <tr>
              <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
              <td>
                <div class="fw-bold">{{ $item->product->name }}</div>
                @if($item->product->name_ur)
                  <div class="text-success small font-urdu">{{ $item->product->name_ur }}</div>
                @endif
              </td>
              <td class="text-end font-monospace fw-semibold">
                {{ number_format($item->quantity_dispatched, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace fw-bold {{ $item->quantity_received !== null ? 'text-success' : 'text-muted' }}">
                {{ $item->quantity_received !== null ? number_format($item->quantity_received, 2) : '-' }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
              </td>
              <td class="text-end font-monospace">
                {{ $tenant->currency }} {{ number_format($item->unit_cost, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold pe-3">
                {{ $tenant->currency }} {{ number_format($item->quantity_dispatched * $item->unit_cost, 2) }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal for Receiving Transfer -->
  @if($stockTransfer->status === 'in_transit')
    <div class="modal fade" id="receiveModal" tabindex="-1" aria-labelledby="receiveModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form action="{{ route('tenant.inventory.transfers.receive', $stockTransfer) }}" method="POST">
          @csrf
          <div class="modal-content">
            <div class="modal-header bg-success-subtle">
              <h5 class="modal-title fw-bold" id="receiveModalLabel">
                <i class="bi bi-check2-circle me-2"></i>{{ __('Acknowledge & Receive Produce at Destination') }}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="small text-muted">
                {{ __('Confirm received physical weight or crate counts. Once acknowledged, inventory stock and FIFO lot batches will be created at the destination branch.') }}
              </p>

              <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>{{ __('Produce') }}</th>
                    <th class="text-end">{{ __('Dispatched') }}</th>
                    <th style="width: 150px;">{{ __('Actual Received') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($stockTransfer->items as $item)
                    <tr>
                      <td>
                        <strong>{{ $item->product->name }}</strong>
                        @if($item->product->name_ur)
                          <span class="text-success small font-urdu ms-1">({{ $item->product->name_ur }})</span>
                        @endif
                      </td>
                      <td class="text-end font-monospace">
                        {{ number_format($item->quantity_dispatched, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
                      </td>
                      <td>
                        <input type="number" step="0.001" name="items[{{ $item->id }}]" class="form-control form-control-sm" value="{{ $item->quantity_dispatched }}" min="0" required>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-success fw-semibold">
                <i class="bi bi-check-circle me-1"></i> {{ __('Confirm Receipt & Update Stock') }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  @endif
@endsection
