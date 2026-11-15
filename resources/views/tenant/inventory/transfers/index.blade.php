@extends('layouts.admin')

@section('title', __('Inter-Branch Stock Transfers'))

@section('content')
  <x-breadcrumb 
    :title="__('Stock Transfers')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Transfers') => route('tenant.inventory.transfers.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Inventory') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.transfers.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Transfer Voucher') }}</span>
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

  <!-- Transfers Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-arrow-left-right me-2 text-primary"></i>{{ __('Branch Transfer History') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Transfer #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Source Branch (From)') }}</th>
            <th>{{ __('Destination (To)') }}</th>
            <th>{{ __('Driver / Vehicle') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transfers as $trf)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.inventory.transfers.show', $trf) }}" class="text-decoration-none text-primary">
                  {{ $trf->transfer_no }}
                </a>
              </td>
              <td>{{ $trf->transfer_date->format('d M, Y') }}</td>
              <td>
                <span class="badge bg-light text-dark border">{{ $trf->fromBranch->name ?? '-' }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $trf->toBranch->name ?? '-' }}</span>
              </td>
              <td class="small text-muted">
                {{ $trf->driver_name ?? '-' }} {{ $trf->vehicle_number ? '(' . $trf->vehicle_number . ')' : '' }}
              </td>
              <td class="text-center">
                @if($trf->status === 'received')
                  <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>{{ __('Received') }}</span>
                @elseif($trf->status === 'in_transit')
                  <span class="badge bg-warning text-dark"><i class="bi bi-truck me-1"></i>{{ __('In Transit') }}</span>
                @else
                  <span class="badge bg-secondary">{{ ucfirst($trf->status) }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('tenant.inventory.transfers.show', $trf) }}" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-eye me-1"></i> {{ __('View') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                {{ __('No stock transfers recorded yet.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($transfers->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $transfers->links() }}
      </div>
    @endif
  </div>
@endsection

