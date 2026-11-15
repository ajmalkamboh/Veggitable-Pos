@extends('layouts.admin')

@section('title', __('Chart of Accounts (COA)'))

@section('content')
  <x-breadcrumb 
    :title="__('Chart of Accounts')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Chart of Accounts') => route('tenant.accounting.coa.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.journal.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-journal-text"></i>
        <span>{{ __('Journal Vouchers') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.trial-balance') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-scale"></i>
        <span>{{ __('Trial Balance') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.coa.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('Add New Account') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @php
    $typeTitles = [
      'asset' => ['title' => __('1. Assets (اثاثہ جات)'), 'icon' => 'bi-wallet', 'color' => 'info'],
      'liability' => ['title' => __('2. Liabilities (واجبات)'), 'icon' => 'bi-shield-exclamation', 'color' => 'warning'],
      'equity' => ['title' => __('3. Equity & Capital (سرمایہ)'), 'icon' => 'bi-bank', 'color' => 'primary'],
      'revenue' => ['title' => __('4. Revenue & Sales Income (آمدنی)'), 'icon' => 'bi-graph-up-arrow', 'color' => 'success'],
      'expense' => ['title' => __('5. Expenses & Cost of Goods Sold (اخراجات)'), 'icon' => 'bi-cash-coin', 'color' => 'danger'],
    ];
  @endphp

  @foreach(['asset', 'liability', 'equity', 'revenue', 'expense'] as $typeKey)
    @php
      $groupAccounts = $accounts[$typeKey] ?? collect();
      $meta = $typeTitles[$typeKey];
    @endphp

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi {{ $meta['icon'] }} me-2 text-{{ $meta['color'] }}"></i>{{ $meta['title'] }}
        </h6>
        <span class="badge bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }} fw-semibold">
          {{ $groupAccounts->count() }} {{ __('Accounts') }}
        </span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3" style="width: 15%;">{{ __('Account Code') }}</th>
              <th style="width: 35%;">{{ __('Account Name & Description') }}</th>
              <th style="width: 20%;">{{ __('Classification') }}</th>
              <th class="text-end" style="width: 15%;">{{ __('Current Balance') }}</th>
              <th class="text-end pe-3" style="width: 15%;">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($groupAccounts as $acc)
              <tr>
                <td class="ps-3 font-monospace fw-bold text-dark">{{ $acc->account_code }}</td>
                <td>
                  <div class="fw-bold text-dark">{{ $acc->name }}</div>
                  @if($acc->name_ur)
                    <div class="text-muted small">{{ $acc->name_ur }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge bg-light text-secondary border small">
                    {{ ucfirst(str_replace('_', ' ', $acc->subtype)) }}
                  </span>
                  @if($acc->is_system)
                    <span class="badge bg-secondary-subtle text-secondary small ms-1">{{ __('System') }}</span>
                  @endif
                </td>
                <td class="text-end fw-bold font-monospace">
                  Rs. {{ number_format($acc->current_balance, 2) }}
                </td>
                <td class="text-end pe-3">
                  <a href="{{ route('tenant.accounting.ledger', ['account_id' => $acc->id]) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('View General Ledger') }}">
                    <i class="bi bi-clock-history me-1"></i> {{ __('Ledger') }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-3 text-muted small">
                  {{ __('No accounts configured under this classification.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endforeach
@endsection

