@extends('layouts.admin')

@section('title', __('Customer Accounts & Ledgers'))

@section('content')
  <x-breadcrumb 
    :title="__('Customer Accounts')" 
    :items="[
      __('Parties') => '#',
      __('Customers') => route('tenant.customers.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.customers.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-person-plus"></i>
        <span>{{ __('Add Customer Account') }}</span>
      </a>
      <a href="{{ route('tenant.suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-truck"></i>
        <span>{{ __('Suppliers Directory') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded p-3 fs-3">
            <i class="bi bi-people"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Customers') }}</span>
            <h4 class="mb-0 fw-bold">{{ $customers->total() }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-success-subtle text-success rounded p-3 fs-3">
            <i class="bi bi-check2-circle"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Active Accounts') }}</span>
            <h4 class="mb-0 fw-bold">{{ $activeCustomersCount }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-warning-subtle text-warning rounded p-3 fs-3">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Customer Receivables') }}</span>
            <h4 class="mb-0 fw-bold text-primary font-monospace">{{ $tenant->currency }} {{ number_format($totalReceivable, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.customers.index') }}" class="row g-3 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search customer, Urdu name, phone, code or business...') }}" value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-12 col-md-3">
          <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Customer Types') }}</option>
            @foreach($types as $key => $label)
              <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <select name="is_active" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Statuses') }}</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('Inactive / Suspended') }}</option>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2 justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Filter') }}</button>
          <a href="{{ route('tenant.customers.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Customers Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Customer / Business') }}</th>
            <th>{{ __('Urdu Title') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Phone / Mobile') }}</th>
            <th>{{ __('City') }}</th>
            <th class="text-end">{{ __('Credit Limit') }}</th>
            <th class="text-end">{{ __('Receivable Balance') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($customers as $customer)
            <tr>
              <td class="ps-3">
                <a href="{{ route('tenant.customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none">
                  {{ $customer->name }}
                </a>
                <div class="small text-muted font-monospace">
                  {{ $customer->code }}
                  @if($customer->contact_person)
                    • {{ $customer->contact_person }}
                  @endif
                </div>
              </td>
              <td>
                <span class="urdu-text">{{ $customer->name_ur ?? '—' }}</span>
              </td>
              <td>
                @if($customer->type === 'retail')
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Retail Walk-in') }}</span>
                @elseif($customer->type === 'hotel_restaurant')
                  <span class="badge bg-info-subtle text-info">{{ __('Hotel / Restaurant') }}</span>
                @elseif($customer->type === 'wholesale_buyer')
                  <span class="badge bg-primary-subtle text-primary">{{ __('Wholesale Buyer') }}</span>
                @else
                  <span class="badge bg-light text-dark border">{{ __('Catering / Mess') }}</span>
                @endif
              </td>
              <td>
                <span class="font-monospace small">{{ $customer->phone ?? $customer->mobile ?? '—' }}</span>
              </td>
              <td>
                <span class="small">{{ $customer->city ?? '—' }}</span>
              </td>
              <td class="text-end font-monospace">
                @if($customer->credit_limit > 0)
                  <span class="badge bg-light text-dark border">{{ $tenant->currency }} {{ number_format($customer->credit_limit, 2) }}</span>
                @else
                  <span class="text-muted small">{{ __('Cash Only') }}</span>
                @endif
              </td>
              <td class="text-end font-monospace">
                <span class="fw-bold {{ $customer->current_balance > 0 ? 'text-primary' : ($customer->current_balance < 0 ? 'text-success' : 'text-muted') }}">
                  {{ $tenant->currency }} {{ number_format($customer->current_balance, 2) }}
                </span>
              </td>
              <td class="text-center">
                @if($customer->is_active)
                  <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Suspended') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.customers.show', $customer) }}" class="btn btn-sm btn-outline-info" title="{{ __('View Ledger') }}">
                    <i class="bi bi-receipt"></i>
                  </a>
                  <a href="{{ route('tenant.customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('tenant.customers.destroy', $customer) }}" onsubmit="return confirm('{{ __('Archive this customer account?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Archive') }}">
                      <i class="bi bi-archive"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-semibold">{{ __('No customer accounts found matching criteria.') }}</p>
                <small>{{ __('Create retail walk-in and credit customer accounts (Hotels, Restaurants, Catering).') }}</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($customers->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $customers->links() }}
      </div>
    @endif
  </div>
@endsection

