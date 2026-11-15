@extends('layouts.admin')

@section('title', __('Trial Balance'))

@section('content')
  <x-breadcrumb 
    :title="__('Trial Balance')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Financial Reports') => '#',
      __('Trial Balance') => route('tenant.accounting.trial-balance')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.coa.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-list-columns"></i>
        <span>{{ __('Chart of Accounts') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.pnl') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-graph-up"></i>
        <span>{{ __('Profit & Loss') }}</span>
      </a>
      <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>{{ __('Print Statement') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- Balance Status Alert -->
  @if($trialBalance['is_balanced'])
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center p-3 mb-4">
      <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
      <div>
        <h6 class="fw-bold mb-0 text-success">{{ __('Trial Balance is in Exact Balance (مساوی ہے)') }}</h6>
        <small class="text-muted">{{ __('Total Debits equal Total Credits. Double-entry integrity is verified.') }}</small>
      </div>
    </div>
  @else
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center p-3 mb-4">
      <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
      <div>
        <h6 class="fw-bold mb-0 text-danger">{{ __('Trial Balance is Out of Balance!') }}</h6>
        <small class="text-muted">{{ __('Difference between total debits and credits: Rs. ') }}{{ number_format(abs($trialBalance['total_debits'] - $trialBalance['total_credits']), 2) }}</small>
      </div>
    </div>
  @endif

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-scale me-2 text-primary"></i>{{ __('Trial Balance as of ') }}{{ date('d M, Y') }}
      </h6>
      <span class="badge bg-light text-dark border font-monospace">{{ count($trialBalance['rows']) }} {{ __('Active Accounts') }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width: 15%;">{{ __('Account Code') }}</th>
            <th style="width: 35%;">{{ __('Account Name & Description') }}</th>
            <th style="width: 20%;">{{ __('Classification Type') }}</th>
            <th class="text-end" style="width: 15%;">{{ __('Debit (بنام / ڈیبٹ)') }}</th>
            <th class="text-end pe-3" style="width: 15%;">{{ __('Credit (جمع / کریڈٹ)') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($trialBalance['rows'] as $r)
            @php $acc = $r['account']; @endphp
            <tr>
              <td class="ps-3 font-monospace fw-bold">{{ $acc->account_code }}</td>
              <td>
                <a href="{{ route('tenant.accounting.ledger', ['account_id' => $acc->id]) }}" class="text-decoration-none fw-semibold text-dark">
                  {{ $acc->name }}
                </a>
                @if($acc->name_ur)
                  <span class="text-muted small d-block">{{ $acc->name_ur }}</span>
                @endif
              </td>
              <td>
                <span class="badge {{ $acc->type_badge_class }}">
                  {{ ucfirst($acc->type) }} - {{ ucfirst(str_replace('_', ' ', $acc->subtype)) }}
                </span>
              </td>
              <td class="text-end font-monospace fw-bold text-primary">
                {{ $r['debit_balance'] > 0 ? 'Rs. ' . number_format($r['debit_balance'], 2) : '-' }}
              </td>
              <td class="text-end pe-3 font-monospace fw-bold text-success">
                {{ $r['credit_balance'] > 0 ? 'Rs. ' . number_format($r['credit_balance'], 2) : '-' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                {{ __('No transaction balances recorded in trial balance.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
        <tfoot class="table-dark fw-bold font-monospace fs-6">
          <tr>
            <td colspan="3" class="ps-3 text-end text-uppercase">{{ __('Total Balance') }}:</td>
            <td class="text-end text-info">Rs. {{ number_format($trialBalance['total_debits'], 2) }}</td>
            <td class="text-end pe-3 text-warning">Rs. {{ number_format($trialBalance['total_credits'], 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
@endsection

