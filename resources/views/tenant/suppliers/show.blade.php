@extends('layouts.admin')

@section('title', $supplier->name)

@section('content')
  <x-breadcrumb 
    :title="$supplier->name" 
    :items="[
      __('Parties') => '#',
      __('Suppliers') => route('tenant.suppliers.index'),
      $supplier->name => '#'
    ]"
  >
    <x-slot name="actions">
      <button type="button" class="btn btn-success btn-sm text-white d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="bi bi-cash-stack"></i>
        <span>{{ __('Pay Supplier') }}</span>
      </button>
      <a href="{{ route('tenant.suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil"></i>
        <span>{{ __('Edit Profile') }}</span>
      </a>
      <a href="{{ route('tenant.suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row g-4 mb-4">
    <!-- Supplier Summary Card -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-1 fw-bold text-dark">{{ $supplier->name }}</h4>
              @if($supplier->name_ur)
                <h5 class="urdu-text text-muted mb-2">{{ $supplier->name_ur }}</h5>
              @endif
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark font-monospace border">Code: {{ $supplier->code }}</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ ucfirst(str_replace('_', ' ', $supplier->type)) }}</span>
                @if($supplier->mandi_stall_no)
                  <span class="badge bg-info-subtle text-info border border-info-subtle">
                    <i class="bi bi-shop me-1"></i> {{ $supplier->mandi_stall_no }}
                  </span>
                @endif
              </div>
            </div>
            @if($supplier->is_active)
              <span class="badge bg-success-subtle text-success fs-6">{{ __('Active Vendor') }}</span>
            @else
              <span class="badge bg-secondary-subtle text-secondary fs-6">{{ __('Suspended') }}</span>
            @endif
          </div>

          <div class="row g-3 border-top pt-3 mt-1">
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Contact Person') }}</span>
              <span class="fw-semibold">{{ $supplier->contact_person ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Phone / Landline') }}</span>
              <span class="fw-semibold font-monospace">{{ $supplier->phone ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Mobile / WhatsApp') }}</span>
              <span class="fw-semibold font-monospace">{{ $supplier->mobile ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Commission Rate') }}</span>
              <span class="fw-semibold">{{ (float) $supplier->commission_rate }}%</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Payment Terms') }}</span>
              <span class="fw-semibold">{{ $supplier->payment_terms_days }} {{ __('Days') }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('City / Mandi') }}</span>
              <span class="fw-semibold">{{ $supplier->city ?? '—' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Balance & Bank Card -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Financial Overview') }}</h6>
        </div>
        <div class="card-body">
          <div class="text-center p-3 bg-light rounded mb-3">
            <small class="text-muted text-uppercase fw-semibold">{{ __('Current Outstanding Payable') }}</small>
            <h3 class="mb-0 fw-bold font-monospace mt-1 {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
              {{ $tenant->currency }} {{ number_format($supplier->current_balance, 2) }}
            </h3>
            <small class="text-muted d-block mt-1">
              {{ $supplier->current_balance > 0 ? __('Payable by you') : ($supplier->current_balance < 0 ? __('Advance paid to vendor') : __('Settled / Nil')) }}
            </small>
          </div>

          <div class="border rounded p-3 small">
            <span class="fw-bold text-dark d-block mb-1">{{ __('Bank Account Details') }}</span>
            <div class="text-muted"><strong>{{ __('Bank') }}:</strong> {{ $supplier->bank_name ?? '—' }}</div>
            <div class="text-muted"><strong>{{ __('Title') }}:</strong> {{ $supplier->bank_account_title ?? '—' }}</div>
            <div class="text-muted font-monospace"><strong>{{ __('Account') }}:</strong> {{ $supplier->bank_account_number ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Supplier Statement / Ledger Entries -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold">{{ __('Supplier Statement & Ledger History') }}</h6>
      <span class="badge bg-light text-dark border">{{ $ledgerEntries->total() }} {{ __('Transactions') }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Date') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Description') }}</th>
            <th class="text-end text-success">{{ __('Debit (Payment)') }}</th>
            <th class="text-end text-danger">{{ __('Credit (Purchase)') }}</th>
            <th class="text-end pe-3">{{ __('Balance') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ledgerEntries as $entry)
            <tr>
              <td class="ps-3 font-monospace small">
                {{ $entry->date->format('Y-m-d') }}
              </td>
              <td>
                <span class="badge bg-light text-dark font-monospace border">
                  {{ $entry->reference_no ?? $entry->reference_type }}
                </span>
              </td>
              <td>
                <div>{{ $entry->description }}</div>
                @if($entry->creator)
                  <small class="text-muted">by {{ $entry->creator->name }}</small>
                @endif
              </td>
              <td class="text-end font-monospace text-success">
                {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}
              </td>
              <td class="text-end font-monospace text-danger">
                {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}
              </td>
              <td class="text-end pe-3 font-monospace fw-bold {{ $entry->balance > 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($entry->balance, 2) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-0 fw-semibold">{{ __('No transactions recorded yet in supplier ledger.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($ledgerEntries->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $ledgerEntries->links() }}
      </div>
    @endif
  </div>

  <!-- Record Payment Modal -->
  <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="{{ route('tenant.suppliers.payment', $supplier) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">{{ __('Record Payment to') }} {{ $supplier->name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Payment Amount') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="0.00" value="{{ $supplier->current_balance > 0 ? $supplier->current_balance : '' }}" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Pay From Financial Account') }} <span class="text-danger">*</span></label>
              <select name="account_id" class="form-select" required>
                <option value="">{{ __('Select cash drawer or bank account...') }}</option>
                @foreach($accounts as $acc)
                  <option value="{{ $acc->id }}">
                    {{ $acc->name }} ({{ ucfirst($acc->type) }}) — {{ __('Balance') }}: {{ $tenant->currency }} {{ number_format($acc->current_balance, 2) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Payment Date') }} <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Transaction / Cheque / Slip No.') }}</label>
              <input type="text" name="reference_no" class="form-control" placeholder="e.g. CHQ-98124, Online IBFT Ref">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Payment Note / Description') }}</label>
              <input type="text" name="description" class="form-control" placeholder="e.g. Mandi auction weekly settlement">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-success">{{ __('Post Payment') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

