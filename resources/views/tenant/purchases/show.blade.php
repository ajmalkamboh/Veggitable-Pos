@extends('layouts.tenant')

@section('title', __('Purchase Invoice') . ' - ' . $purchaseInvoice->invoice_no)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('tenant.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="h4 mb-0 fw-bold">{{ $purchaseInvoice->invoice_no }}</h2>
                @if($purchaseInvoice->status === 'received')
                    <span class="badge bg-success">{{ __('Received') }}</span>
                @elseif($purchaseInvoice->status === 'cancelled')
                    <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                @else
                    <span class="badge bg-warning text-dark">{{ ucfirst($purchaseInvoice->status) }}</span>
                @endif

                @if($purchaseInvoice->payment_status === 'paid')
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>{{ __('Paid') }}</span>
                @elseif($purchaseInvoice->payment_status === 'partial')
                    <span class="badge bg-warning text-dark"><i class="bi bi-pie-chart me-1"></i>{{ __('Partial') }}</span>
                @else
                    <span class="badge bg-danger"><i class="bi bi-clock me-1"></i>{{ __('Unpaid') }}</span>
                @endif
            </div>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Purchased on') }} {{ $purchaseInvoice->date->format('d M, Y') }} | {{ __('Branch:') }} {{ $purchaseInvoice->branch->name ?? 'Default Branch' }}
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('tenant.purchases.print', $purchaseInvoice) }}" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> {{ __('Print Voucher') }}
            </a>
            @if($purchaseInvoice->status !== 'cancelled')
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#debitNoteModal">
                    <i class="bi bi-arrow-return-left me-1"></i> {{ __('Debit Note / Return') }}
                </button>
                <form action="{{ route('tenant.purchases.destroy', $purchaseInvoice) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to cancel this purchase invoice? All inventory entries and supplier ledger balances will be reversed.') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i> {{ __('Cancel Invoice') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        {{-- Supplier & Mandi Stall Details --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-shop me-2"></i>{{ __('Supplier & Mandi Stall') }}
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">
                        <a href="{{ route('tenant.suppliers.show', $purchaseInvoice->supplier) }}" class="text-decoration-none text-dark">
                            {{ $purchaseInvoice->supplier->name }}
                        </a>
                    </h5>
                    @if($purchaseInvoice->supplier->mandi_name || $purchaseInvoice->supplier->stall_number)
                        <div class="text-success small fw-semibold mb-2">
                            <i class="bi bi-geo-alt me-1"></i>{{ $purchaseInvoice->supplier->mandi_name }} {{ $purchaseInvoice->supplier->stall_number ? '(' . __('Stall/Shop #') . ' ' . $purchaseInvoice->supplier->stall_number . ')' : '' }}
                        </div>
                    @endif
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><i class="bi bi-telephone me-2"></i>{{ $purchaseInvoice->supplier->phone }}</li>
                        @if($purchaseInvoice->supplier->commission_agent_name)
                            <li><i class="bi bi-person-badge me-2"></i>{{ __('Arhti / Agent:') }} {{ $purchaseInvoice->supplier->commission_agent_name }}</li>
                        @endif
                        @if($purchaseInvoice->supplier_invoice_no)
                            <li class="mt-2 text-dark"><strong>{{ __('Mandi Parchi / Ref #:') }}</strong> {{ $purchaseInvoice->supplier_invoice_no }}</li>
                        @endif
                        <li><strong>{{ __('Due Date:') }}</strong> {{ $purchaseInvoice->due_date ? $purchaseInvoice->due_date->format('d M, Y') : '-' }}</li>
                    </ul>

                    <div class="p-2 rounded bg-light border">
                        <div class="d-flex justify-content-between small text-muted">
                            <span>{{ __('Supplier Current Balance:') }}</span>
                            <span class="fw-bold {{ $purchaseInvoice->supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $tenant->currency }} {{ number_format($purchaseInvoice->supplier->current_balance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mandi Cost Breakdown --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-receipt me-2"></i>{{ __('Mandi Cost Breakdown') }}
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="ps-3 py-2 text-muted">{{ __('Produce Subtotal') }}:</td>
                                <td class="pe-3 py-2 text-end fw-semibold">{{ $tenant->currency }} {{ number_format($purchaseInvoice->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-1 text-muted">{{ __('Mandi Commission') }} ({{ $purchaseInvoice->commission_rate }}%):</td>
                                <td class="pe-3 py-1 text-end">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->commission_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-1 text-muted">{{ __('Mandi / Market Fee (Chungi)') }}:</td>
                                <td class="pe-3 py-1 text-end">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->mandi_fee, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3 py-1 text-muted">{{ __('Freight / Labour (Mazdoori)') }}:</td>
                                <td class="pe-3 py-1 text-end">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->freight_charges, 2) }}</td>
                            </tr>
                            @if($purchaseInvoice->other_charges > 0)
                                <tr>
                                    <td class="ps-3 py-1 text-muted">{{ __('Other Charges') }}:</td>
                                    <td class="pe-3 py-1 text-end">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->other_charges, 2) }}</td>
                                </tr>
                            @endif
                            @if($purchaseInvoice->discount > 0)
                                <tr>
                                    <td class="ps-3 py-1 text-success">{{ __('Discount') }}:</td>
                                    <td class="pe-3 py-1 text-end text-success">- {{ $tenant->currency }} {{ number_format($purchaseInvoice->discount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="table-light border-top">
                                <td class="ps-3 py-2 fw-bold">{{ __('Total Mandi Payable') }}:</td>
                                <td class="pe-3 py-2 text-end fw-bold fs-6 text-primary">{{ $tenant->currency }} {{ number_format($purchaseInvoice->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment & Settlement Status --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-credit-card me-2"></i>{{ __('Payment Settlement') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">{{ __('Paid Amount:') }}</span>
                            <span class="fw-bold text-success">{{ $tenant->currency }} {{ number_format($purchaseInvoice->paid_amount, 2) }}</span>
                        </div>
                        @if($purchaseInvoice->financialAccount)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-wallet2 me-1"></i> {{ __('Paid from:') }} {{ $purchaseInvoice->financialAccount->name }}
                            </div>
                        @endif

                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">{{ __('Due Balance:') }}</span>
                            <span class="fw-bold fs-6 text-danger">{{ $tenant->currency }} {{ number_format($purchaseInvoice->due_amount, 2) }}</span>
                        </div>
                    </div>

                    @if($purchaseInvoice->attachment_path)
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block mb-1">{{ __('Mandi Slip / Parchi Photo') }}</small>
                            <a href="{{ asset('storage/' . $purchaseInvoice->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-image me-1"></i> {{ __('View Slip Photo') }}
                            </a>
                        </div>
                    @endif

                    @if($purchaseInvoice->notes)
                        <div class="mt-3 small text-muted">
                            <strong>{{ __('Notes:') }}</strong> {{ $purchaseInvoice->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Line Items Table --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bi bi-box-seam me-2 text-success"></i>{{ __('Produce Lots & Items') }}
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Lot Number') }}</th>
                        <th class="text-center">{{ __('Units/Bags') }}</th>
                        <th class="text-end">{{ __('Gross Wt') }}</th>
                        <th class="text-end">{{ __('Tare Wt') }}</th>
                        <th class="text-end">{{ __('Net Wt') }}</th>
                        <th class="text-end">{{ __('Rate / Unit') }}</th>
                        <th class="text-end pe-3">{{ __('Line Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseInvoice->items as $index => $item)
                        <tr>
                            <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold">{{ $item->product->name }}</div>
                                @if($item->product->name_ur)
                                    <div class="text-success small font-urdu">{{ $item->product->name_ur }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary font-monospace">{{ $item->lot_number }}</span>
                            </td>
                            <td class="text-center">
                                {{ number_format($item->quantity, 2) }} {{ $item->unit->short_name ?? '' }}
                            </td>
                            <td class="text-end text-muted">
                                {{ $item->gross_weight ? number_format($item->gross_weight, 2) . ' kg' : '-' }}
                            </td>
                            <td class="text-end text-danger">
                                {{ $item->tare_weight ? number_format($item->tare_weight, 2) . ' kg' : '-' }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                {{ $item->net_weight ? number_format($item->net_weight, 2) . ' kg' : number_format($item->quantity, 2) . ' ' . ($item->unit->short_name ?? '') }}
                            </td>
                            <td class="text-end">
                                {{ $tenant->currency }} {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="text-end fw-bold pe-3">
                                {{ $tenant->currency }} {{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="8" class="text-end fw-bold">{{ __('Produce Subtotal:') }}</th>
                        <th class="text-end pe-3 fw-bold">{{ $tenant->currency }} {{ number_format($purchaseInvoice->subtotal, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Debit Notes / Purchase Returns Section --}}
    @if($purchaseInvoice->returns && $purchaseInvoice->returns->count() > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold text-warning">
                    <i class="bi bi-arrow-return-left me-2"></i>{{ __('Debit Notes / Purchase Returns') }}
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('Return #') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th class="text-end pe-3">{{ __('Adjusted Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseInvoice->returns as $ret)
                            <tr>
                                <td class="ps-3 fw-bold font-monospace text-primary">{{ $ret->return_no }}</td>
                                <td>{{ $ret->date->format('d M, Y') }}</td>
                                <td>{{ $ret->reason }}</td>
                                <td class="text-end pe-3 fw-bold text-danger">
                                    - {{ $tenant->currency }} {{ number_format($ret->total_amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- Debit Note / Return Modal --}}
<div class="modal fade" id="debitNoteModal" tabindex="-1" aria-labelledby="debitNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('tenant.purchases.returns.store', $purchaseInvoice) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title fw-bold" id="debitNoteModalLabel">
                        <i class="bi bi-arrow-return-left me-2"></i>{{ __('Create Debit Note / Return Produce') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        {{ __('Record rejected, spoiled or returned produce from this Mandi invoice. This will automatically deduct from the supplier balance and record a debit note in the supplier ledger.') }}
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">{{ __('Return Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">{{ __('Return Reason') }} <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control" placeholder="{{ __('e.g., Rotten produce, weight discrepancy, crate rejection') }}" required>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">{{ __('Select Items to Return') }}:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Invoiced Qty / Net Wt') }}</th>
                                    <th style="width: 130px;">{{ __('Return Qty') }}</th>
                                    <th style="width: 140px;">{{ __('Unit Rate') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseInvoice->items as $idx => $item)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                            <strong>{{ $item->product->name }}</strong>
                                            @if($item->product->name_ur)
                                                <span class="text-success small font-urdu ms-1">({{ $item->product->name_ur }})</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $item->net_weight ? number_format($item->net_weight, 2) . ' kg' : number_format($item->quantity, 2) . ' ' . ($item->unit->short_name ?? '') }}
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="0" min="0" max="{{ $item->net_weight ?? $item->quantity }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm" value="{{ $item->unit_price }}" min="0">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('Post Debit Note') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

