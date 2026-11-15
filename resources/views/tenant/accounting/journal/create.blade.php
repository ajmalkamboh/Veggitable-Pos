@extends('layouts.admin')

@section('title', __('Create Journal Voucher (JV)'))

@section('content')
  <x-breadcrumb 
    :title="__('New Journal Voucher')" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Journal Vouchers') => route('tenant.accounting.journal.index'),
      __('New Voucher') => route('tenant.accounting.journal.create')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.journal.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
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

  <form method="POST" action="{{ route('tenant.accounting.journal.store') }}" id="jvForm">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-journal-text me-2 text-primary"></i>{{ __('Journal Voucher Header') }}
        </h6>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold required">{{ __('Branch Outlet') }}</label>
            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
              @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id', $branchId) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold required">{{ __('Entry Date') }}</label>
            <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Reference / Voucher #') }}</label>
            <input type="text" name="reference_no" class="form-control" placeholder="e.g. Bank slip, Cheque #, Memo #" value="{{ old('reference_no') }}">
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold required">{{ __('Narration / Description (وضاحت)') }}</label>
            <textarea name="narration" rows="2" class="form-control" placeholder="{{ __('Explain the purpose of this double-entry journal transaction...') }}" required>{{ old('narration') }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Double-Entry Lines Section -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold">
          <i class="bi bi-list-check me-2 text-primary"></i>{{ __('Double-Entry Accounts (Debits & Credits)') }}
        </h6>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
          <i class="bi bi-plus-circle me-1"></i> {{ __('Add Account Line') }}
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="linesTable">
          <thead class="table-light">
            <tr>
              <th style="width: 38%;">{{ __('Account Name & Code') }}</th>
              <th style="width: 26%;">{{ __('Line Description') }}</th>
              <th style="width: 16%;" class="text-end">{{ __('Debit (بنام / ڈیبٹ)') }}</th>
              <th style="width: 16%;" class="text-end">{{ __('Credit (جمع / کریڈٹ)') }}</th>
              <th style="width: 4%;" class="text-center">{{ __('Action') }}</th>
            </tr>
          </thead>
          <tbody id="linesBody">
            <!-- Dynamic lines injected by JavaScript -->
          </tbody>
          <tfoot class="table-light fw-bold font-monospace">
            <tr>
              <td colspan="2" class="text-end">{{ __('Totals') }}:</td>
              <td class="text-end text-primary" id="footerTotalDebit">Rs. 0.00</td>
              <td class="text-end text-success" id="footerTotalCredit">Rs. 0.00</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="2" class="text-end">{{ __('Status') }}:</td>
              <td colspan="3" id="balanceStatus">
                <span class="badge bg-danger p-2 fs-12">{{ __('Unbalanced: Debits must equal Credits') }}</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
      <a href="{{ route('tenant.accounting.journal.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
      <button type="submit" class="btn btn-primary px-4 fw-bold" id="submitBtn" disabled>
        <i class="bi bi-check2-circle me-1"></i> {{ __('Post Journal Voucher') }}
      </button>
    </div>
  </form>

  <template id="lineTemplate">
    <tr class="line-row">
      <td>
        <select name="lines[INDEX][account_id]" class="form-select form-select-sm account-select" required>
          <option value="">{{ __('Select Account...') }}</option>
          @foreach($accounts as $acc)
            <option value="{{ $acc->id }}">
              [{{ $acc->account_code }}] {{ $acc->name }} @if($acc->name_ur) - {{ $acc->name_ur }} @endif ({{ ucfirst($acc->type) }})
            </option>
          @endforeach
        </select>
      </td>
      <td>
        <input type="text" name="lines[INDEX][description]" class="form-control form-control-sm" placeholder="{{ __('Line memo') }}">
      </td>
      <td>
        <input type="number" step="0.01" min="0" name="lines[INDEX][debit]" class="form-control form-control-sm text-end debit-input font-monospace" placeholder="0.00" value="0.00">
      </td>
      <td>
        <input type="number" step="0.01" min="0" name="lines[INDEX][credit]" class="form-control form-control-sm text-end credit-input font-monospace" placeholder="0.00" value="0.00">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  </template>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let lineIndex = 0;
      const linesBody = document.getElementById('linesBody');
      const lineTemplate = document.getElementById('lineTemplate').innerHTML;
      const addLineBtn = document.getElementById('addLineBtn');
      const submitBtn = document.getElementById('submitBtn');
      const footerDebit = document.getElementById('footerTotalDebit');
      const footerCredit = document.getElementById('footerTotalCredit');
      const balanceStatus = document.getElementById('balanceStatus');

      function addLine() {
        const html = lineTemplate.replace(/INDEX/g, lineIndex++);
        linesBody.insertAdjacentHTML('beforeend', html);
        attachEvents(linesBody.lastElementChild);
        calc();
      }

      function attachEvents(row) {
        const debitInput = row.querySelector('.debit-input');
        const creditInput = row.querySelector('.credit-input');
        const removeBtn = row.querySelector('.remove-line-btn');

        debitInput.addEventListener('input', function () {
          if (parseFloat(debitInput.value) > 0) {
            creditInput.value = '0.00';
          }
          calc();
        });

        creditInput.addEventListener('input', function () {
          if (parseFloat(creditInput.value) > 0) {
            debitInput.value = '0.00';
          }
          calc();
        });

        removeBtn.addEventListener('click', function () {
          if (linesBody.querySelectorAll('.line-row').length > 2) {
            row.remove();
            calc();
          } else {
            alert('{{ __("A double-entry voucher requires at least two lines.") }}');
          }
        });
      }

      function calc() {
        let totalD = 0;
        let totalC = 0;

        document.querySelectorAll('.line-row').forEach(row => {
          totalD += (parseFloat(row.querySelector('.debit-input').value) || 0);
          totalC += (parseFloat(row.querySelector('.credit-input').value) || 0);
        });

        totalD = Math.round(totalD * 100) / 100;
        totalC = Math.round(totalC * 100) / 100;

        footerDebit.textContent = 'Rs. ' + totalD.toFixed(2);
        footerCredit.textContent = 'Rs. ' + totalC.toFixed(2);

        const diff = Math.abs(totalD - totalC);

        if (totalD > 0 && diff < 0.009) {
          balanceStatus.innerHTML = '<span class="badge bg-success p-2 fs-12"><i class="bi bi-check-circle me-1"></i> {{ __("Balanced (مساوی)") }}</span>';
          submitBtn.disabled = false;
        } else {
          balanceStatus.innerHTML = '<span class="badge bg-danger p-2 fs-12"><i class="bi bi-exclamation-triangle me-1"></i> {{ __("Unbalanced Difference: Rs. ") }}' + diff.toFixed(2) + '</span>';
          submitBtn.disabled = true;
        }
      }

      addLineBtn.addEventListener('click', addLine);

      // Add minimum 2 initial lines
      addLine();
      addLine();
    });
  </script>
  @endpush
@endsection

