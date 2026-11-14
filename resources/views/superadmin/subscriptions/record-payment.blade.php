@extends('layouts.admin')

@section('title', 'Record Manual Subscription Payment')

@section('content')
  <x-breadcrumb 
    title="Record Manual Subscription Payment" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Subscriptions' => '#',
      'Payments' => route('superadmin.subscriptions.payments'),
      'Record' => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Payments</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="mb-0 fw-bold">Manual Payment Entry</h6>
          <small class="text-muted">Record off-line cash, bank transfer, or mobile wallet payment for a tenant.</small>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('superadmin.subscriptions.store') }}">
            @csrf

            <!-- Select Tenant -->
            <div class="mb-3">
              <label for="tenant_id" class="form-label fw-semibold">Business / Tenant <span class="text-danger">*</span></label>
              <select name="tenant_id" id="tenant_id" class="form-select @error('tenant_id') is-invalid @enderror" required>
                <option value="">-- Select Active Business --</option>
                @foreach($tenants as $tenant)
                  <option value="{{ $tenant->id }}" {{ old('tenant_id', request('tenant_id')) == $tenant->id ? 'selected' : '' }}>
                    {{ $tenant->name }} ({{ $tenant->slug }}) - Plan: {{ $tenant->plan?->name ?? 'None' }}
                  </option>
                @endforeach
              </select>
              @error('tenant_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Amount -->
            <div class="mb-3">
              <label for="amount" class="form-label fw-semibold">Payment Amount (Rs.) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">PKR</span>
                <input 
                  type="number" 
                  step="0.01" 
                  name="amount" 
                  id="amount" 
                  class="form-control @error('amount') is-invalid @enderror" 
                  placeholder="e.g. 3500.00" 
                  value="{{ old('amount') }}" 
                  required
                >
                @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Payment Method -->
            <div class="mb-3">
              <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
              <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer / Online IBFT</option>
                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque / Pay Order</option>
                <option value="easypaisa" {{ old('payment_method') === 'easypaisa' ? 'selected' : '' }}>EasyPaisa</option>
                <option value="jazzcash" {{ old('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                <option value="manual" {{ old('payment_method') === 'manual' ? 'selected' : '' }}>Other / Manual</option>
              </select>
              @error('payment_method')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Transaction Reference -->
            <div class="mb-3">
              <label for="transaction_reference" class="form-label fw-semibold">Transaction Reference / Cheque No.</label>
              <input 
                type="text" 
                name="transaction_reference" 
                id="transaction_reference" 
                class="form-control font-monospace @error('transaction_reference') is-invalid @enderror" 
                placeholder="e.g. TXN-9842183 or Cheque # 104829" 
                value="{{ old('transaction_reference') }}"
              >
              @error('transaction_reference')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">Bank transaction reference, IBFT tracking ID, or deposit slip number.</div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
              <label for="notes" class="form-label fw-semibold">Administrative Notes</label>
              <textarea 
                name="notes" 
                id="notes" 
                rows="3" 
                class="form-control @error('notes') is-invalid @enderror" 
                placeholder="Optional notes regarding this subscription payment..."
              >{{ old('notes') }}</textarea>
              @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <i class="bi bi-check2-circle"></i>
                <span>Record & Generate Invoice</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

