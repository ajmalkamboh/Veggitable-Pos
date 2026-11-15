@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
  <x-breadcrumb 
    :title="$customer->name" 
    :items="[
      __('Parties') => '#',
      __('Customers') => route('tenant.customers.index'),
      $customer->name => '#'
    ]"
  >
    <x-slot name="actions">
      <button type="button" class="btn btn-success btn-sm text-white d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#recordReceiptModal">
        <i class="bi bi-wallet2"></i>
        <span>{{ __('Receive Payment') }}</span>
      </button>
      <a href="{{ route('tenant.customers.edit', $customer) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil"></i>
        <span>{{ __('Edit Account') }}</span>
      </a>
      <a href="{{ route('tenant.customers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row g-4 mb-4">
    <!-- Customer Summary Card -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-1 fw-bold text-dark">{{ $customer->name }}</h4>
              @if($customer->name_ur)
                <h5 class="urdu-text text-muted mb-2">{{ $customer->name_ur }}</h5>
              @endif
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-dark font-monospace border">Account: {{ $customer->code }}</span>
                <span class="badge bg-info-subtle text-info border border-info-subtle">{{ ucfirst(str_replace('_', ' ', $customer->type)) }}</span>
                @if($customer->tax_id)
                  <span class="badge bg-light text-dark border">NTN/CNIC: {{ $customer->tax_id }}</span>
                @endif
              </div>
            </div>
            @if($customer->is_active)
              <span class="badge bg-success-subtle text-success fs-6">{{ __('Active Account') }}</span>
            @else
              <span class="badge bg-secondary-subtle text-secondary fs-6">{{ __('Suspended') }}</span>
            @endif
          </div>

          <div class="row g-3 border-top pt-3 mt-1">
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Contact Person') }}</span>
              <span class="fw-semibold">{{ $customer->contact_person ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Telephone') }}</span>
              <span class="fw-semibold font-monospace">{{ $customer->phone ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Mobile / WhatsApp') }}</span>
              <span class="fw-semibold font-monospace">{{ $customer->mobile ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Allowed Credit Days') }}</span>
              <span class="fw-semibold">{{ $customer->credit_days }} {{ __('Days') }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('City') }}</span>
              <span class="fw-semibold">{{ $customer->city ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-muted small d-block">{{ __('Delivery Address') }}</span>
              <span class="fw-semibold">{{ $customer->address ?? '—' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Balance & Credit Limit Card -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Receivables & Credit Limit') }}</h6>
        </div>
        <div class="card-body">
          <div class="text-center p-3 bg-light rounded mb-3">
            <small class="text-muted text-uppercase fw-semibold">{{ __('Current Outstanding Receivable') }}</small>
            <h3 class="mb-0 fw-bold font-monospace mt-1 text-primary">
              {{ $tenant->currency }} {{ number_format($customer->current_balance, 2) }}
            </h3>
            <small class="text-muted d-block mt-1">
              {{ $customer->current_balance > 0 ? __('Receivable from customer') : ($customer->current_balance < 0 ? __('Advance deposit received') : __('Account cleared')) }}
            </small>
          </div>

          <!-- Credit Limit Gauge -->
          <div class="border rounded p-3">
            <div class="d-flex justify-content-between align-items-center mb-1 small">
              <span class="fw-semibold text-muted">{{ __('Credit Limit') }}:</span>
              <span class="fw-bold font-monospace">{{ $tenant->currency }} {{ number_format($customer->credit_limit, 2) }}</span>
            </div>
            @php
              $limitPercent = $customer->credit_limit > 0 ? min(100, round(($customer->current_balance / $customer->credit_limit) * 100)) : 0;
            @endphp
            <div class="progress" style="height: 8px;">
              <div class="progress-bar {{ $limitPercent > 80 ? 'bg-danger' : ($limitPercent > 50 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $limitPercent }}%;" aria-valuenow="{{ $limitPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted d-block mt-1 text-end">{{ $limitPercent }}% {{ __('utilized') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Customer Statement / Ledger Entries -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h6 class="card-title mb-0 fw-bold">{{ __('Customer Account Statement & Ledger') }}</h6>
      <span class="badge bg-light text-dark border">{{ $ledgerEntries->total() }} {{ __('Transactions') }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Date') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Description') }}</th>
            <th class="text-end text-danger">{{ __('Debit (Sales / Invoice)') }}</th>
            <th class="text-end text-success">{{ __('Credit (Receipt / Return)') }}</th>
            <th class="text-end pe-3">{{ __('Receivable Balance') }}</th>
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
              <td class="text-end font-monospace text-danger">
                {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}
              </td>
              <td class="text-end font-monospace text-success">
                {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}
              </td>
              <td class="text-end pe-3 font-monospace fw-bold text-primary">
                {{ number_format($entry->balance, 2) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-0 fw-semibold">{{ __('No transactions recorded in customer ledger yet.') }}</p>
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

  <!-- Record Payment Receipt Modal -->
  <div class="modal fade" id="recordReceiptModal" tabindex="-1" aria-labelledby="recordReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="{{ route('tenant.customers.receipt', $customer) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="recordReceiptModalLabel">{{ __('Receive Payment from') }} {{ $customer->name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Receipt Amount') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="0.00" value="{{ $customer->current_balance > 0 ? $customer->current_balance : '' }}" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Deposit Into Financial Account') }} <span class="text-danger">*</span></label>
              <select name="account_id" class="form-select" required>
                <option value="">{{ __('Select cash till or bank account...') }}</option>
                @foreach($accounts as $acc)
                  <option value="{{ $acc->id }}">
                    {{ $acc->name }} ({{ ucfirst($acc->type) }}) — {{ __('Balance') }}: {{ $tenant->currency }} {{ number_format($acc->current_balance, 2) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Receipt Date') }} <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Receipt / Slip / Cheque No.') }}</label>
              <input type="text" name="reference_no" class="form-control" placeholder="e.g. REC-54120, Cash Receipt">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Payment Note / Description') }}</label>
              <input type="text" name="description" class="form-control" placeholder="e.g. Hotel weekly bill clearance">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-success">{{ __('Post Receipt') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

