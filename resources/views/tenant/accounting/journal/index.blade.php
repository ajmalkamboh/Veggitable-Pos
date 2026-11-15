@extends('layouts.admin')

@section('title', __('Journal Vouchers (JV)'))

@section('content')
  <x-breadcrumb 
    :title="__('Journal Vouchers')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Journal Vouchers') => route('tenant.accounting.journal.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.coa.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-list-columns"></i>
        <span>{{ __('Chart of Accounts') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.trial-balance') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-scale"></i>
        <span>{{ __('Trial Balance') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.journal.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Journal Voucher') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.accounting.journal.index') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search JV #, narration, or ref...') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select form-select-sm">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>{{ __('Posted') }}</option>
            <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>{{ __('Reversed') }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="{{ __('From Date') }}">
        </div>
        <div class="col-md-2">
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="{{ __('To Date') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
            <i class="bi bi-filter me-1"></i> {{ __('Filter') }}
          </button>
          <a href="{{ route('tenant.accounting.journal.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Journal Vouchers Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-journal-text me-2 text-primary"></i>{{ __('General Journal Vouchers Registry') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('JV #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Type & Ref') }}</th>
            <th style="width: 30%;">{{ __('Narration') }}</th>
            <th class="text-end">{{ __('Total Debit (Rs)') }}</th>
            <th class="text-end">{{ __('Total Credit (Rs)') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th>{{ __('Posted By') }}</th>
            <th class="text-end pe-3">{{ __('Action') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($entries as $jv)
            <tr>
              <td class="ps-3 font-monospace fw-bold">
                <a href="{{ route('tenant.accounting.journal.show', $jv->id) }}" class="text-decoration-none text-primary">
                  {{ $jv->entry_no }}
                </a>
              </td>
              <td>{{ $jv->date->format('d M, Y') }}</td>
              <td><span class="badge bg-light text-dark border">{{ $jv->branch->name }}</span></td>
              <td>
                <span class="badge bg-secondary-subtle text-secondary small">{{ ucfirst($jv->reference_type) }}</span>
                @if($jv->reference_no)
                  <small class="text-muted d-block font-monospace">{{ $jv->reference_no }}</small>
                @endif
              </td>
              <td>
                <div class="text-truncate" style="max-width: 280px;" title="{{ $jv->narration }}">
                  {{ $jv->narration }}
                </div>
              </td>
              <td class="text-end fw-bold font-monospace text-primary">
                Rs. {{ number_format($jv->total_debit, 2) }}
              </td>
              <td class="text-end fw-bold font-monospace text-success">
                Rs. {{ number_format($jv->total_credit, 2) }}
              </td>
              <td class="text-center">
                <span class="badge {{ $jv->status_badge_class }}">
                  {{ ucfirst($jv->status) }}
                </span>
              </td>
              <td><small class="text-muted">{{ $jv->creator?->name ?? 'System' }}</small></td>
              <td class="text-end pe-3">
                <a href="{{ route('tenant.accounting.journal.show', $jv->id) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('View Voucher') }}">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                {{ __('No journal vouchers posted.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($entries->hasPages())
      <div class="card-footer bg-white border-top py-3">
        {{ $entries->withQueryString()->links() }}
      </div>
    @endif
  </div>
@endsection

