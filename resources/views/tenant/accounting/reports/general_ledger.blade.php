@extends('layouts.admin')

@section('title', __('General Ledger Statement'))

@section('content')
  <x-breadcrumb 
    :title="__('General Ledger')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Financial Reports') => '#',
      __('General Ledger') => route('tenant.accounting.ledger')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.coa.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-list-columns"></i>
        <span>{{ __('Chart of Accounts') }}</span>
      </a>
      <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>{{ __('Print Ledger') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('tenant.accounting.ledger') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">{{ __('Select Account') }}</label>
          <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($accounts as $acc)
              <option value="{{ $acc->id }}" {{ $selectedAccount && $selectedAccount->id == $acc->id ? 'selected' : '' }}>
                [{{ $acc->account_code }}] {{ $acc->name }} @if($acc->name_ur) - {{ $acc->name_ur }} @endif ({{ ucfirst($acc->type) }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">{{ __('From Date') }}</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">{{ __('To Date') }}</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn btn-sm btn-primary w-100">
            <i class="bi bi-filter"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  @if($selectedAccount && $ledgerData)
    <!-- Account Summary Header -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-md-6">
            <span class="badge {{ $selectedAccount->type_badge_class }} mb-1">
              {{ ucfirst($selectedAccount->type) }} - {{ ucfirst(str_replace('_', ' ', $selectedAccount->subtype)) }}
            </span>
            <h4 class="fw-bold text-dark mb-0">
              <span class="font-monospace text-primary">[{{ $selectedAccount->account_code }}]</span>
              {{ $selectedAccount->name }}
            </h4>
            @if($selectedAccount->name_ur)
              <div class="text-muted">{{ $selectedAccount->name_ur }}</div>
            @endif
          </div>
          <div class="col-md-3 text-md-end mt-3 mt-md-0">
            <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Opening Balance') }}</span>
            <h5 class="fw-bold mb-0 text-secondary">Rs. {{ number_format($ledgerData['opening_balance'], 2) }}</h5>
          </div>
          <div class="col-md-3 text-md-end mt-3 mt-md-0">
            <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Current / Closing Balance') }}</span>
            <h4 class="fw-bold mb-0 text-success">Rs. {{ number_format($ledgerData['closing_balance'], 2) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- Ledger Lines Table -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-journal-bookmark me-2 text-primary"></i>{{ __('Statement of Transactions') }}
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3" style="width: 12%;">{{ __('Date') }}</th>
              <th style="width: 15%;">{{ __('Voucher #') }}</th>
              <th style="width: 37%;">{{ __('Description / Narration') }}</th>
              <th class="text-end" style="width: 12%;">{{ __('Debit (بنام)') }}</th>
              <th class="text-end" style="width: 12%;">{{ __('Credit (جمع)') }}</th>
              <th class="text-end pe-3" style="width: 12%;">{{ __('Running Balance') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr class="table-light">
              <td class="ps-3 font-monospace">-</td>
              <td class="font-monospace text-muted">-</td>
              <td class="fw-semibold text-muted">{{ __('Beginning Opening Balance') }}</td>
              <td class="text-end">-</td>
              <td class="text-end">-</td>
              <td class="text-end pe-3 font-monospace fw-bold">
                Rs. {{ number_format($ledgerData['opening_balance'], 2) }}
              </td>
            </tr>

            @forelse($ledgerData['rows'] as $r)
              <tr>
                <td class="ps-3">{{ $r['entry']->date->format('d M, Y') }}</td>
                <td class="font-monospace fw-bold">
                  <a href="{{ route('tenant.accounting.journal.show', $r['entry']->id) }}" class="text-decoration-none text-primary">
                    {{ $r['entry']->entry_no }}
                  </a>
                </td>
                <td>
                  <div>{{ $r['line']->description }}</div>
                  @if($r['line']->description !== $r['entry']->narration)
                    <small class="text-muted">{{ $r['entry']->narration }}</small>
                  @endif
                </td>
                <td class="text-end font-monospace text-primary fw-semibold">
                  {{ $r['debit'] > 0 ? 'Rs. ' . number_format($r['debit'], 2) : '-' }}
                </td>
                <td class="text-end font-monospace text-success fw-semibold">
                  {{ $r['credit'] > 0 ? 'Rs. ' . number_format($r['credit'], 2) : '-' }}
                </td>
                <td class="text-end pe-3 font-monospace fw-bold text-dark">
                  Rs. {{ number_format($r['balance'], 2) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  {{ __('No transactions found for this account in the selected date range.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
          <tfoot class="table-light fw-bold font-monospace fs-6">
            <tr>
              <td colspan="5" class="ps-3 text-end text-uppercase">{{ __('Closing Balance') }}:</td>
              <td class="text-end pe-3 text-success">Rs. {{ number_format($ledgerData['closing_balance'], 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  @endif
@endsection

