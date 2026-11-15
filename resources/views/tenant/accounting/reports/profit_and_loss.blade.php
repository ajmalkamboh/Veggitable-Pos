@extends('layouts.admin')

@section('title', __('Profit & Loss Statement (آمدنی و اخراجات)'))

@section('content')
  <x-breadcrumb 
    :title="__('Profit & Loss Statement')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Financial Reports') => '#',
      __('Profit & Loss') => route('tenant.accounting.pnl')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.balance-sheet') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-bank"></i>
        <span>{{ __('Balance Sheet') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.trial-balance') }}" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-scale"></i>
        <span>{{ __('Trial Balance') }}</span>
      </a>
      <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>{{ __('Print P&L') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- Summary KPI Card -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-success border-4">
        <div class="card-body p-3">
          <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Total Revenue') }}</span>
          <h3 class="mb-0 fw-bold text-success mt-1">Rs. {{ number_format($pnl['total_revenue'], 2) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-danger border-4">
        <div class="card-body p-3">
          <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Cost of Goods Sold (COGS)') }}</span>
          <h3 class="mb-0 fw-bold text-danger mt-1">Rs. {{ number_format($pnl['total_cogs'], 2) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-primary border-4">
        <div class="card-body p-3">
          <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Gross Profit') }}</span>
          <h3 class="mb-0 fw-bold text-primary mt-1">Rs. {{ number_format($pnl['gross_profit'], 2) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-{{ $pnl['net_profit'] >= 0 ? 'success' : 'danger' }} border-4">
        <div class="card-body p-3">
          <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Net Profit / Loss') }}</span>
          <h3 class="mb-0 fw-bold text-{{ $pnl['net_profit'] >= 0 ? 'success' : 'danger' }} mt-1">
            Rs. {{ number_format($pnl['net_profit'], 2) }}
          </h3>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h6 class="card-title mb-0 fw-bold">
        <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>{{ __('Statement of Income & Expenses') }}
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 font-monospace">
        <tbody>
          <!-- 1. Revenue Section -->
          <tr class="table-success fw-bold text-uppercase">
            <th colspan="2" class="ps-3">{{ __('1. Operating Revenue & Sales Income (آمدنی)') }}</th>
            <th class="text-end pe-3">{{ __('Amount (PKR)') }}</th>
          </tr>
          @forelse($pnl['revenue_rows'] as $r)
            <tr>
              <td class="ps-4" style="width: 20%;">[{{ $r['account']->account_code }}]</td>
              <td style="width: 55%;">{{ $r['account']->name }} @if($r['account']->name_ur) ({{ $r['account']->name_ur }}) @endif</td>
              <td class="text-end pe-3">Rs. {{ number_format($r['amount'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="ps-4 text-muted">{{ __('No revenue recorded.') }}</td>
            </tr>
          @endforelse
          <tr class="fw-bold bg-light">
            <td colspan="2" class="ps-3 text-end text-uppercase">{{ __('Total Operating Revenue') }}:</td>
            <td class="text-end pe-3 text-success fs-6">Rs. {{ number_format($pnl['total_revenue'], 2) }}</td>
          </tr>

          <!-- 2. Cost of Goods Sold Section -->
          <tr class="table-danger fw-bold text-uppercase">
            <th colspan="2" class="ps-3">{{ __('2. Cost of Goods Sold (خریداری لاگت)') }}</th>
            <th class="text-end pe-3">{{ __('Amount (PKR)') }}</th>
          </tr>
          @forelse($pnl['cogs_rows'] as $r)
            <tr>
              <td class="ps-4">[{{ $r['account']->account_code }}]</td>
              <td>{{ $r['account']->name }} @if($r['account']->name_ur) ({{ $r['account']->name_ur }}) @endif</td>
              <td class="text-end pe-3">Rs. {{ number_format($r['amount'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="ps-4 text-muted">{{ __('No cost of goods sold recorded.') }}</td>
            </tr>
          @endforelse
          <tr class="fw-bold bg-light">
            <td colspan="2" class="ps-3 text-end text-uppercase">{{ __('Total Cost of Goods Sold') }}:</td>
            <td class="text-end pe-3 text-danger fs-6">Rs. {{ number_format($pnl['total_cogs'], 2) }}</td>
          </tr>

          <!-- Gross Profit Summary -->
          <tr class="table-primary fw-bold fs-6">
            <td colspan="2" class="ps-3 text-uppercase">{{ __('Gross Profit / Margin (مجموعی منافع)') }}:</td>
            <td class="text-end pe-3 text-primary">Rs. {{ number_format($pnl['gross_profit'], 2) }}</td>
          </tr>

          <!-- 3. Operating Expenses Section -->
          <tr class="table-warning fw-bold text-uppercase">
            <th colspan="2" class="ps-3">{{ __('3. Shop Operating Overheads (دکان کے عمومی اخراجات)') }}</th>
            <th class="text-end pe-3">{{ __('Amount (PKR)') }}</th>
          </tr>
          @forelse($pnl['expense_rows'] as $r)
            <tr>
              <td class="ps-4">[{{ $r['account']->account_code }}]</td>
              <td>{{ $r['account']->name }} @if($r['account']->name_ur) ({{ $r['account']->name_ur }}) @endif</td>
              <td class="text-end pe-3">Rs. {{ number_format($r['amount'], 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="ps-4 text-muted">{{ __('No operating overheads recorded.') }}</td>
            </tr>
          @endforelse
          <tr class="fw-bold bg-light">
            <td colspan="2" class="ps-3 text-end text-uppercase">{{ __('Total Operating Expenses') }}:</td>
            <td class="text-end pe-3 text-warning fs-6">Rs. {{ number_format($pnl['total_operating_expenses'], 2) }}</td>
          </tr>

          <!-- Net Profit / Loss -->
          <tr class="table-dark fw-bold fs-5">
            <td colspan="2" class="ps-3 text-uppercase">
              {{ $pnl['net_profit'] >= 0 ? __('Net Profit (خالص منافع)') : __('Net Loss (خالص نقصان)') }}:
            </td>
            <td class="text-end pe-3 text-{{ $pnl['net_profit'] >= 0 ? 'success' : 'danger' }}">
              Rs. {{ number_format($pnl['net_profit'], 2) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection

