@extends('layouts.admin')

@section('title', __('Produce Sorting & Regrading'))

@section('content')
  <x-breadcrumb 
    :title="__('Produce Sorting & Regrading')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Wastage') => route('tenant.inventory.wastage.index'),
      __('Regradings') => route('tenant.inventory.regradings.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.wastage.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Wastage') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.regradings.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-shuffle"></i>
        <span>{{ __('New Produce Regrading') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-shuffle me-2 text-primary"></i>{{ __('Produce Sorting & Quality Regrading Sessions') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Regrading #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Branch Outlet') }}</th>
            <th>{{ __('Source Produce (Input)') }}</th>
            <th>{{ __('Target Produce (Output)') }}</th>
            <th class="text-end">{{ __('Sorting Cull / Shrinkage') }}</th>
            <th class="text-end">{{ __('Loss Value') }}</th>
            <th>{{ __('Created By') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($regradings as $reg)
            <tr>
              <td class="ps-3 font-monospace fw-bold text-primary">{{ $reg->regrading_no }}</td>
              <td>{{ $reg->date->format('d M, Y') }}</td>
              <td><span class="badge bg-light text-dark border">{{ $reg->branch->name }}</span></td>
              <td>
                <div class="fw-bold">{{ $reg->sourceProduct->name }}</div>
                <div class="small text-muted">{{ number_format($reg->source_quantity, 2) }} kg @ Rs. {{ number_format($reg->source_unit_cost, 2) }}</div>
              </td>
              <td>
                <div class="fw-bold text-success">{{ $reg->targetProduct->name }}</div>
                <div class="small text-muted">{{ number_format($reg->target_quantity, 2) }} kg @ Rs. {{ number_format($reg->target_unit_cost, 2) }}</div>
              </td>
              <td class="text-end">
                @if($reg->wastage_quantity > 0)
                  <span class="badge bg-danger-subtle text-danger fw-bold">
                    {{ number_format($reg->wastage_quantity, 2) }} kg
                  </span>
                @else
                  <span class="badge bg-light text-secondary">0.00 kg</span>
                @endif
              </td>
              <td class="text-end fw-bold text-danger">Rs. {{ number_format($reg->loss_cost, 2) }}</td>
              <td><small class="text-muted">{{ $reg->creator?->name ?? 'System' }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                {{ __('No regrading sessions recorded.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($regradings->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $regradings->withQueryString()->links() }}
      </div>
    @endif
  </div>
@endsection

