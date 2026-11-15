@extends('layouts.admin')

@section('title', __('Suppliers & Commission Agents'))

@section('content')
  <x-breadcrumb 
    :title="__('Suppliers Directory')" 
    :items="[
      __('Parties') => '#',
      __('Suppliers') => route('tenant.suppliers.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.suppliers.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-person-plus"></i>
        <span>{{ __('Add Supplier / Aarthi') }}</span>
      </a>
      <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-people"></i>
        <span>{{ __('Customer Accounts') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded p-3 fs-3">
            <i class="bi bi-truck"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Suppliers') }}</span>
            <h4 class="mb-0 fw-bold">{{ $suppliers->total() }}</h4>
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
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Active Vendors') }}</span>
            <h4 class="mb-0 fw-bold">{{ $activeSuppliersCount }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm py-2">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="bg-danger-subtle text-danger rounded p-3 fs-3">
            <i class="bi bi-wallet2"></i>
          </div>
          <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Total Outstanding Payable') }}</span>
            <h4 class="mb-0 fw-bold text-danger font-monospace">{{ $tenant->currency }} {{ number_format($totalPayable, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.suppliers.index') }}" class="row g-3 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search supplier, Urdu name, phone, code or Mandi stall...') }}" value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-12 col-md-3">
          <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Supplier Types') }}</option>
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
          <a href="{{ route('tenant.suppliers.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Suppliers Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Supplier / Aarthi') }}</th>
            <th>{{ __('Urdu Title') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Mandi Stall') }}</th>
            <th>{{ __('Contact Phone') }}</th>
            <th>{{ __('Commission') }}</th>
            <th class="text-end">{{ __('Payable Balance') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($suppliers as $supplier)
            <tr>
              <td class="ps-3">
                <a href="{{ route('tenant.suppliers.show', $supplier) }}" class="fw-bold text-dark text-decoration-none">
                  {{ $supplier->name }}
                </a>
                <div class="small text-muted font-monospace">
                  {{ $supplier->code }}
                  @if($supplier->contact_person)
                    • {{ $supplier->contact_person }}
                  @endif
                </div>
              </td>
              <td>
                <span class="urdu-text">{{ $supplier->name_ur ?? '—' }}</span>
              </td>
              <td>
                @if($supplier->type === 'commission_agent')
                  <span class="badge bg-primary-subtle text-primary">{{ __('Aarthi') }}</span>
                @elseif($supplier->type === 'farmer')
                  <span class="badge bg-success-subtle text-success">{{ __('Farmer') }}</span>
                @elseif($supplier->type === 'wholesaler')
                  <span class="badge bg-info-subtle text-info">{{ __('Wholesaler') }}</span>
                @else
                  <span class="badge bg-light text-dark border">{{ __('Other') }}</span>
                @endif
              </td>
              <td>
                <span class="small">{{ $supplier->mandi_stall_no ?? '—' }}</span>
              </td>
              <td>
                <span class="font-monospace small">{{ $supplier->phone ?? $supplier->mobile ?? '—' }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ (float) $supplier->commission_rate }}%</span>
              </td>
              <td class="text-end font-monospace">
                <span class="fw-bold {{ $supplier->current_balance > 0 ? 'text-danger' : ($supplier->current_balance < 0 ? 'text-success' : 'text-muted') }}">
                  {{ $tenant->currency }} {{ number_format($supplier->current_balance, 2) }}
                </span>
              </td>
              <td class="text-center">
                @if($supplier->is_active)
                  <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Suspended') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-info" title="{{ __('View Ledger') }}">
                    <i class="bi bi-receipt"></i>
                  </a>
                  <a href="{{ route('tenant.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('tenant.suppliers.destroy', $supplier) }}" onsubmit="return confirm('{{ __('Archive this supplier?') }}')">
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
                <i class="bi bi-truck fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-semibold">{{ __('No suppliers found matching criteria.') }}</p>
                <small>{{ __('Add your Subzi Mandi commission agents (Aarthi) and farm growers.') }}</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($suppliers->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $suppliers->links() }}
      </div>
    @endif
  </div>
@endsection

