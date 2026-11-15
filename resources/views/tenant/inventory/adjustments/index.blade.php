@extends('layouts.admin')

@section('title', __('Physical Stock Audits & Adjustments'))

@section('content')
  <x-breadcrumb 
    :title="__('Stock Adjustments')" 
    :items="[
      __('Inventory') => route('tenant.inventory.index'),
      __('Adjustments') => route('tenant.inventory.adjustments.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to Inventory') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.adjustments.create') }}" class="btn btn-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Physical Count Audit') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Adjustments Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-clipboard-check me-2 text-warning"></i>{{ __('Audit & Stock Reconciliation Records') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Adjustment #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Branch Outlet') }}</th>
            <th>{{ __('Audit Reason') }}</th>
            <th>{{ __('Items Reconciled') }}</th>
            <th>{{ __('Audited By') }}</th>
            <th class="text-end pe-3">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($adjustments as $adj)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.inventory.adjustments.show', $adj) }}" class="text-decoration-none text-primary">
                  {{ $adj->adjustment_no }}
                </a>
              </td>
              <td>{{ $adj->date->format('d M, Y') }}</td>
              <td>
                <span class="badge bg-light text-dark border">{{ $adj->branch->name ?? '-' }}</span>
              </td>
              <td>
                <span class="badge bg-secondary-subtle text-dark border">
                  {{ ucfirst(str_replace('_', ' ', $adj->reason)) }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $adj->items->count() }} {{ __('Produce Items') }}</span>
              </td>
              <td class="small text-muted">{{ $adj->creator->name ?? 'Staff' }}</td>
              <td class="text-end pe-3">
                <a href="{{ route('tenant.inventory.adjustments.show', $adj) }}" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-eye me-1"></i> {{ __('View Audit Report') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                {{ __('No physical stock adjustments recorded yet.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($adjustments->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $adjustments->links() }}
      </div>
    @endif
  </div>
@endsection
