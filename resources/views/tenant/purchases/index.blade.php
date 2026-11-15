@extends('layouts.admin')

@section('title', __('Mandi Purchases & Invoices'))

@section('content')
  <x-breadcrumb 
    :title="__('Purchase Invoices')" 
    :items="[
      __('Procurement') => '#',
      __('Purchases') => route('tenant.purchases.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-plus"></i>
        <span>{{ __('New Mandi Purchase') }}</span>
      </a>
      <a href="{{ route('tenant.suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-truck"></i>
        <span>{{ __('Suppliers Directory') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded p-3 fs-3">
            <i class="bi bi-cart-check"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Purchases') }}</span>
            <h4 class="mb-0 fw-bold font-monospace">{{ $tenant->currency }} {{ number_format($totalPurchases, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-danger-subtle text-danger rounded p-3 fs-3">
            <i class="bi bi-exclamation-circle"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Outstanding Supplier Payables') }}</span>
            <h4 class="mb-0 fw-bold font-monospace text-danger">{{ $tenant->currency }} {{ number_format($totalUnpaid, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success rounded p-3 fs-3">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Invoices Count') }}</span>
            <h4 class="mb-0 fw-bold">{{ $invoices->total() }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.purchases.index') }}" class="row g-3 align-items-center">
        <!-- Search -->
        <div class="col-12 col-md-3">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search invoice #, parchi #, supplier...') }}" value="{{ request('search') }}">
          </div>
        </div>

        <!-- Supplier Filter -->
        <div class="col-12 col-md-3">
          <select name="supplier_id" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Suppliers / Aarthi') }}</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Payment Status -->
        <div class="col-12 col-md-2">
          <select name="payment_status" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Payments') }}</option>
            <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>{{ __('Unpaid') }}</option>
            <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>{{ __('Partial') }}</option>
            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>{{ __('Paid') }}</option>
          </select>
        </div>

        <!-- Date Range -->
        <div class="col-12 col-md-2">
          <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" title="{{ __('Start Date') }}">
        </div>

        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Filter') }}</button>
          <a href="{{ route('tenant.purchases.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Invoices Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Invoice #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Supplier / Aarthi') }}</th>
            <th>{{ __('Branch') }}</th>
            <th class="text-end">{{ __('Subtotal') }}</th>
            <th class="text-end">{{ __('Total Bill') }}</th>
            <th class="text-end">{{ __('Paid') }}</th>
            <th class="text-center">{{ __('Payment') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $invoice)
            <tr>
              <td class="ps-3">
                <a href="{{ route('tenant.purchases.show', $invoice) }}" class="fw-bold font-monospace text-dark text-decoration-none">
                  {{ $invoice->invoice_no }}
                </a>
                @if($invoice->supplier_invoice_no)
                  <div class="small text-muted font-monospace">Parchi: {{ $invoice->supplier_invoice_no }}</div>
                @endif
              </td>
              <td>
                <span class="font-monospace small">{{ $invoice->date->format('Y-m-d') }}</span>
              </td>
              <td>
                <div class="fw-semibold text-dark">{{ $invoice->supplier->name }}</div>
                @if($invoice->supplier->name_ur)
                  <small class="urdu-text text-muted">{{ $invoice->supplier->name_ur }}</small>
                @endif
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $invoice->branch->name }}</span>
              </td>
              <td class="text-end font-monospace">
                {{ number_format($invoice->subtotal, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold text-dark">
                {{ $tenant->currency }} {{ number_format($invoice->total_amount, 2) }}
              </td>
              <td class="text-end font-monospace text-success">
                {{ number_format($invoice->paid_amount, 2) }}
              </td>
              <td class="text-center">
                @if($invoice->payment_status === 'paid')
                  <span class="badge bg-success-subtle text-success">{{ __('Paid') }}</span>
                @elseif($invoice->payment_status === 'partial')
                  <span class="badge bg-warning-subtle text-warning">{{ __('Partial') }}</span>
                @else
                  <span class="badge bg-danger-subtle text-danger">{{ __('Unpaid') }}</span>
                @endif
              </td>
              <td class="text-center">
                @if($invoice->status === 'received')
                  <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Received') }}</span>
                @elseif($invoice->status === 'cancelled')
                  <span class="badge bg-danger-subtle text-danger">{{ __('Cancelled') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Draft') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.purchases.print', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-info" title="{{ __('Print Voucher') }}">
                    <i class="bi bi-printer"></i>
                  </a>
                  <a href="{{ route('tenant.purchases.show', $invoice) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('View Details') }}">
                    <i class="bi bi-eye"></i>
                  </a>
                  @if($invoice->status === 'received')
                    <form method="POST" action="{{ route('tenant.purchases.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Cancel this purchase invoice and reverse supplier ledger?') }}')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Cancel Invoice') }}">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-semibold">{{ __('No purchase invoices found matching criteria.') }}</p>
                <small>{{ __('Record Mandi auction lot purchases, vegetable weight tare deductions and supplier bills.') }}</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($invoices->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $invoices->links() }}
      </div>
    @endif
  </div>
@endsection

