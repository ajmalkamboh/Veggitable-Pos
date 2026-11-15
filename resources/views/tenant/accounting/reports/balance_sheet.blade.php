@extends('layouts.admin')

@section('title', __('Balance Sheet (مالیاتی پوزیشن / بیلنس شیٹ)'))

@section('content')
  <x-breadcrumb 
    :title="__('Balance Sheet')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Financial Reports') => '#',
      __('Balance Sheet') => route('tenant.accounting.balance-sheet')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.pnl') }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-graph-up"></i>
        <span>{{ __('Profit & Loss') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.trial-balance') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-scale"></i>
        <span>{{ __('Trial Balance') }}</span>
      </a>
      <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>{{ __('Print Balance Sheet') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- Equation Verification Banner -->
  <div class="alert alert-{{ $bs['is_balanced'] ? 'success' : 'danger' }} border-0 shadow-sm d-flex align-items-center p-3 mb-4">
    <i class="bi bi-{{ $bs['is_balanced'] ? 'check-circle-fill text-success' : 'exclamation-triangle-fill text-danger' }} fs-4 me-3"></i>
    <div>
      <h6 class="fw-bold mb-0 text-{{ $bs['is_balanced'] ? 'success' : 'danger' }}">
        {{ __('Accounting Equation: Assets = Liabilities + Equity') }}
      </h6>
      <small class="text-muted">
        Rs. {{ number_format($bs['total_assets'], 2) }} (Assets) = Rs. {{ number_format($bs['total_liabilities'], 2) }} (Liabilities) + Rs. {{ number_format($bs['total_equity'], 2) }} (Equity)
      </small>
    </div>
  </div>

  <div class="row g-4">
    <!-- Assets Column -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-info-subtle border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-wallet2 me-2 text-info"></i>{{ __('1. Total Assets (اثاثہ جات)') }}
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 font-monospace">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Account Code & Name') }}</th>
                <th class="text-end pe-3">{{ __('Amount (PKR)') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bs['assets'] as $a)
                <tr>
                  <td class="ps-3">
                    <span class="text-muted">[{{ $a->account_code }}]</span>
                    <span class="fw-semibold text-dark">{{ $a->name }}</span>
                    @if($a->name_ur)
                      <div class="small text-muted">{{ $a->name_ur }}</div>
                    @endif
                  </td>
                  <td class="text-end pe-3 fw-bold text-primary">
                    Rs. {{ number_format($a->current_balance, 2) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center py-4 text-muted">{{ __('No assets recorded.') }}</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fw-bold font-monospace fs-6">
              <tr>
                <td class="ps-3 text-uppercase">{{ __('Total Assets') }}:</td>
                <td class="text-end pe-3 text-info">Rs. {{ number_format($bs['total_assets'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Liabilities & Equity Column -->
    <div class="col-lg-6">
      <!-- Liabilities Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning-subtle border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-shield-exclamation me-2 text-warning"></i>{{ __('2. Liabilities (واجبات)') }}
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 font-monospace">
            <tbody>
              @forelse($bs['liabilities'] as $l)
                <tr>
                  <td class="ps-3">
                    <span class="text-muted">[{{ $l->account_code }}]</span>
                    <span class="fw-semibold text-dark">{{ $l->name }}</span>
                    @if($l->name_ur)
                      <div class="small text-muted">{{ $l->name_ur }}</div>
                    @endif
                  </td>
                  <td class="text-end pe-3 fw-bold text-warning">
                    Rs. {{ number_format($l->current_balance, 2) }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center py-3 text-muted">{{ __('No liabilities recorded.') }}</td>
                </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fw-bold font-monospace">
              <tr>
                <td class="ps-3 text-uppercase">{{ __('Total Liabilities') }}:</td>
                <td class="text-end pe-3 text-warning">Rs. {{ number_format($bs['total_liabilities'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Equity Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary-subtle border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-bank me-2 text-primary"></i>{{ __('3. Owner Equity & Capital (سرمایہ)') }}
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 font-monospace">
            <tbody>
              @foreach($bs['equity'] as $e)
                <tr>
                  <td class="ps-3">
                    <span class="text-muted">[{{ $e->account_code }}]</span>
                    <span class="fw-semibold text-dark">{{ $e->name }}</span>
                    @if($e->name_ur)
                      <div class="small text-muted">{{ $e->name_ur }}</div>
                    @endif
                  </td>
                  <td class="text-end pe-3 fw-bold text-primary">
                    Rs. {{ number_format($e->current_balance, 2) }}
                  </td>
                </tr>
              @endforeach
              <tr class="table-light">
                <td class="ps-3">
                  <span class="fw-semibold text-success">{{ __('Retained Earnings / Net Profit (منافع)') }}</span>
                </td>
                <td class="text-end pe-3 fw-bold text-{{ $bs['net_profit'] >= 0 ? 'success' : 'danger' }}">
                  Rs. {{ number_format($bs['net_profit'], 2) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light fw-bold font-monospace">
              <tr>
                <td class="ps-3 text-uppercase">{{ __('Total Equity') }}:</td>
                <td class="text-end pe-3 text-primary">Rs. {{ number_format($bs['total_equity'], 2) }}</td>
              </tr>
              <tr class="table-dark fs-6">
                <td class="ps-3 text-uppercase">{{ __('Total Liabilities & Equity') }}:</td>
                <td class="text-end pe-3 text-info">Rs. {{ number_format($bs['total_liabilities_and_equity'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

