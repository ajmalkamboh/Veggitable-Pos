@extends('layouts.admin')

@section('title', __('Record Expense / Petty Cash'))

@section('content')
  <x-breadcrumb 
    :title="__('Record Expense')" 
    :items="[
      __('Dashboard') => route('tenant.records.index'),
      __('Expenses') => route('tenant.expenses.index'),
      __('Record Expense') => route('tenant.expenses.create')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.expenses.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Cancel') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Please correct the following errors:') }}</div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('tenant.expenses.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-wallet2 me-2 text-primary"></i>{{ __('Expense Details') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold required">{{ __('Branch Outlet') }}</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ old('branch_id', $activeBranchId) == $b->id ? 'selected' : '' }}>
                      {{ $b->name }}
                    </option>
                  @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold required">{{ __('Expense Date') }}</label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold required">{{ __('Expense Category') }}</label>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                  <option value="">{{ __('Select Expense Category...') }}</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                      {{ $cat->name }} @if($cat->name_ur) ({{ $cat->name_ur }}) @endif
                    </option>
                  @endforeach
                </select>
                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold required">{{ __('Amount (PKR)') }}</label>
                <div class="input-group">
                  <span class="input-group-text fw-bold">Rs.</span>
                  <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold text-danger @error('amount') is-invalid @enderror" placeholder="0.00" value="{{ old('amount') }}" required>
                </div>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold required">{{ __('Payment Method') }}</label>
                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                  <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>{{ __('Cash Drawer (کیش)') }}</option>
                  <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>{{ __('Bank Transfer (بینک)') }}</option>
                  <option value="online" {{ old('payment_method') === 'online' ? 'selected' : '' }}>{{ __('JazzCash / EasyPaisa / Online (جازکیش / ایزی پیسہ)') }}</option>
                  <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>{{ __('Cheque (چیک)') }}</option>
                </select>
                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Paid From Account (Petty Cash / Bank)') }}</label>
                <select name="financial_account_id" class="form-select">
                  <option value="">{{ __('None / Direct Cash') }}</option>
                  @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('financial_account_id') == $acc->id ? 'selected' : '' }}>
                      {{ $acc->name }} ({{ ucfirst($acc->type) }}) - [Balance: Rs. {{ number_format($acc->current_balance, 2) }}]
                    </option>
                  @endforeach
                </select>
                <small class="text-muted fs-11">{{ __('Selecting an account will automatically deduct the expense amount.') }}</small>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Payee / Recipient Name') }}</label>
                <input type="text" name="payee_name" class="form-control" placeholder="{{ __('e.g., Suzuki Driver Asif, WAPDA, Aslam Tea Boy') }}" value="{{ old('payee_name') }}">
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Receipt / Bill / Cheque #') }}</label>
                <input type="text" name="reference_no" class="form-control" placeholder="{{ __('e.g., Bill Ref, Cash Receipt #, Cheque #') }}" value="{{ old('reference_no') }}">
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Description & Notes') }}</label>
                <textarea name="description" rows="3" class="form-control" placeholder="{{ __('e.g., Morning Mandi Suzuki carriage fare for 30 crates of tomatoes...') }}">{{ old('description') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Receipt Attachment Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-paperclip me-2 text-primary"></i>{{ __('Receipt / Bill Attachment') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <label class="form-label fw-semibold">{{ __('Upload Photo / PDF') }}</label>
            <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
            <small class="text-muted d-block mt-1 fs-11">
              {{ __('Attach a photo of the cash memo, petrol slip, or utility bill (Max 5MB).') }}
            </small>
          </div>
        </div>

        <!-- Recurring Option Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-arrow-repeat me-2 text-primary"></i>{{ __('Recurring Schedule') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="isRecurring" {{ old('is_recurring') ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="isRecurring">
                {{ __('Mark as Recurring Expense') }}
              </label>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold">{{ __('Frequency') }}</label>
              <select name="recurring_frequency" class="form-select form-select-sm">
                <option value="monthly" {{ old('recurring_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly (Shop Rent, Electricity)') }}</option>
                <option value="weekly" {{ old('recurring_frequency') === 'weekly' ? 'selected' : '' }}>{{ __('Weekly (Wages, Supplies)') }}</option>
                <option value="daily" {{ old('recurring_frequency') === 'daily' ? 'selected' : '' }}>{{ __('Daily (Tea, Carriage)') }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Approval Workflow -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-bottom py-3">
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-shield-check me-2 text-primary"></i>{{ __('Authorization') }}
            </h6>
          </div>
          <div class="card-body p-4">
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="status" id="statusApproved" value="approved" checked>
              <label class="form-check-label fw-semibold text-success" for="statusApproved">
                {{ __('Approve & Deduct Immediately') }}
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="status" id="statusPending" value="pending_approval">
              <label class="form-check-label fw-semibold text-warning" for="statusPending">
                {{ __('Submit as Draft / Pending Approval') }}
              </label>
            </div>
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-lg fw-bold">
            <i class="bi bi-check2-circle me-1"></i> {{ __('Save Expense Voucher') }}
          </button>
          <a href="{{ route('tenant.expenses.index') }}" class="btn btn-outline-secondary">
            {{ __('Cancel') }}
          </a>
        </div>
      </div>
    </div>
  </form>
@endsection

