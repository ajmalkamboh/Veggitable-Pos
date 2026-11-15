@extends('layouts.admin')

@section('title', __('Journal Voucher: ') . $entry->entry_no)

@section('content')
  <x-breadcrumb 
    :title="$entry->entry_no" 
    :items="[
      __('Accounting') => route('tenant.accounting.coa.index'),
      __('Journal Vouchers') => route('tenant.accounting.journal.index'),
      $entry->entry_no => route('tenant.accounting.journal.show', $entry->id)
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.accounting.journal.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to List') }}</span>
      </a>
      <a href="{{ route('tenant.accounting.journal.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('New Journal Voucher') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-title mb-0 fw-bold">
              <i class="bi bi-journal-text me-2 text-primary"></i>{{ __('Journal Voucher Transaction Lines') }}
            </h6>
            <small class="text-muted font-monospace">{{ $entry->entry_no }}</small>
          </div>
          <span class="badge {{ $entry->status_badge_class }} fs-12 px-3 py-2">
            {{ ucfirst($entry->status) }}
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3" style="width: 38%;">{{ __('Account Name & Code') }}</th>
                <th style="width: 32%;">{{ __('Line Memo') }}</th>
                <th class="text-end" style="width: 15%;">{{ __('Debit (بنام)') }}</th>
                <th class="text-end pe-3" style="width: 15%;">{{ __('Credit (جمع)') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($entry->lines as $line)
                <tr>
                  <td class="ps-3">
                    <span class="font-monospace fw-bold text-dark">[{{ $line->account->account_code }}]</span>
                    <span class="fw-semibold text-primary">{{ $line->account->name }}</span>
                    @if($line->account->name_ur)
                      <div class="text-muted small">{{ $line->account->name_ur }}</div>
                    @endif
                  </td>
                  <td>
                    <span class="text-secondary small">{{ $line->description ?? '-' }}</span>
                  </td>
                  <td class="text-end font-monospace fw-bold text-primary">
                    {{ $line->debit > 0 ? 'Rs. ' . number_format($line->debit, 2) : '-' }}
                  </td>
                  <td class="text-end pe-3 font-monospace fw-bold text-success">
                    {{ $line->credit > 0 ? 'Rs. ' . number_format($line->credit, 2) : '-' }}
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="table-light fw-bold font-monospace fs-6">
              <tr>
                <td colspan="2" class="ps-3 text-end">{{ __('Totals') }}:</td>
                <td class="text-end text-primary">Rs. {{ number_format($entry->total_debit, 2) }}</td>
                <td class="text-end pe-3 text-success">Rs. {{ number_format($entry->total_credit, 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Voucher Meta Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-info-circle me-2 text-primary"></i>{{ __('Voucher Overview') }}
          </h6>
        </div>
        <div class="card-body p-3">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Voucher #') }}:</span>
              <span class="fw-bold font-monospace">{{ $entry->entry_no }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Date') }}:</span>
              <span class="fw-semibold">{{ $entry->date->format('d M, Y') }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Branch Outlet') }}:</span>
              <span class="fw-semibold">{{ $entry->branch->name }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Source Type') }}:</span>
              <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($entry->reference_type) }}</span>
            </li>
            @if($entry->reference_no)
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">{{ __('Ref Number') }}:</span>
                <span class="font-monospace">{{ $entry->reference_no }}</span>
              </li>
            @endif
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Posted By') }}:</span>
              <span class="fw-semibold">{{ $entry->creator?->name ?? 'System' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">{{ __('Posted At') }}:</span>
              <span>{{ $entry->created_at->format('d M, Y H:i') }}</span>
            </li>
          </ul>

          <div class="mt-3 p-3 bg-light rounded small">
            <span class="fw-bold text-muted d-block mb-1">{{ __('Narration (تفصیل)') }}:</span>
            <div class="text-dark">{{ $entry->narration }}</div>
          </div>

          @if($entry->status === 'posted')
            <div class="mt-4 pt-3 border-top">
              <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#reverseModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Reverse This Journal Entry') }}
              </button>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Reversal Modal -->
  @if($entry->status === 'posted')
    <div class="modal fade" id="reverseModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" action="{{ route('tenant.accounting.journal.reverse', $entry->id) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-danger">{{ __('Reverse Journal Voucher') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">
              {{ __('This will create an automatic offsetting journal entry flipping all debits and credits, and mark this voucher as reversed.') }}
            </p>
            <div class="mb-3">
              <label class="form-label fw-semibold required">{{ __('Reason for Reversal') }}</label>
              <textarea name="reason" rows="3" class="form-control" required placeholder="{{ __('e.g., Wrong account credited or amount typo...') }}"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-danger btn-sm">{{ __('Confirm Reversal') }}</button>
          </div>
        </form>
      </div>
    </div>
  @endif
@endsection

