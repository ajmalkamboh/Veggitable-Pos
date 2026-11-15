<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Mandi Purchase Voucher') }} - {{ $purchaseInvoice->invoice_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            background-color: #fff;
            padding: 20px;
        }
        .font-urdu {
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', serif;
        }
        .voucher-header {
            border-bottom: 2px solid #198754;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .table-items th {
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6;
        }
        .border-dashed {
            border-top: 1px dashed #6c757d;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid max-w-1000 mx-auto">
        {{-- Print Action Controls --}}
        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light border rounded no-print">
            <div>
                <strong>{{ __('Voucher:') }}</strong> {{ $purchaseInvoice->invoice_no }}
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    {{ __('Print Now') }}
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary">
                    {{ __('Close') }}
                </button>
            </div>
        </div>

        {{-- Header Section --}}
        <div class="voucher-header">
            <div class="row align-items-center">
                <div class="col-7">
                    <h2 class="fw-bold text-success mb-0">{{ $tenant->name }}</h2>
                    <p class="text-muted small mb-0">
                        {{ $tenant->address ?? '' }} | {{ __('Phone:') }} {{ $tenant->phone ?? '-' }}
                    </p>
                    <span class="badge bg-light text-dark border mt-1">
                        {{ __('Branch:') }} {{ $purchaseInvoice->branch->name ?? 'Main Branch' }}
                    </span>
                </div>
                <div class="col-5 text-end">
                    <h4 class="fw-bold mb-0 text-dark">{{ __('MANDI PURCHASE VOUCHER') }}</h4>
                    <div class="font-urdu fs-5 text-success">منڈی خریداری واؤچر</div>
                    <div class="mt-1 small">
                        <strong>{{ __('Voucher #:') }}</strong> <span class="font-monospace fw-bold">{{ $purchaseInvoice->invoice_no }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Meta & Supplier Details --}}
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="p-3 border rounded bg-light">
                    <h6 class="fw-bold text-primary mb-2">{{ __('Supplier / Mandi Stall Information') }}</h6>
                    <div class="fw-bold fs-6">{{ $purchaseInvoice->supplier->name }}</div>
                    @if($purchaseInvoice->supplier->mandi_name || $purchaseInvoice->supplier->stall_number)
                        <div class="small text-muted">
                            <strong>{{ __('Mandi:') }}</strong> {{ $purchaseInvoice->supplier->mandi_name }} 
                            @if($purchaseInvoice->supplier->stall_number)
                                ({{ __('Stall #') }}: {{ $purchaseInvoice->supplier->stall_number }})
                            @endif
                        </div>
                    @endif
                    <div class="small text-muted"><strong>{{ __('Phone:') }}</strong> {{ $purchaseInvoice->supplier->phone }}</div>
                    @if($purchaseInvoice->supplier->commission_agent_name)
                        <div class="small text-muted"><strong>{{ __('Arhti / Agent:') }}</strong> {{ $purchaseInvoice->supplier->commission_agent_name }}</div>
                    @endif
                </div>
            </div>

            <div class="col-6">
                <div class="p-3 border rounded bg-light">
                    <h6 class="fw-bold text-primary mb-2">{{ __('Voucher Details') }}</h6>
                    <div class="row small g-1">
                        <div class="col-6 text-muted">{{ __('Purchase Date:') }}</div>
                        <div class="col-6 text-end fw-bold">{{ $purchaseInvoice->date->format('d M, Y') }}</div>

                        <div class="col-6 text-muted">{{ __('Due Date:') }}</div>
                        <div class="col-6 text-end">{{ $purchaseInvoice->due_date ? $purchaseInvoice->due_date->format('d M, Y') : '-' }}</div>

                        @if($purchaseInvoice->supplier_invoice_no)
                            <div class="col-6 text-muted">{{ __('Mandi Ref / Parchi #:') }}</div>
                            <div class="col-6 text-end fw-bold">{{ $purchaseInvoice->supplier_invoice_no }}</div>
                        @endif

                        <div class="col-6 text-muted">{{ __('Status:') }}</div>
                        <div class="col-6 text-end fw-semibold text-uppercase">{{ $purchaseInvoice->status }}</div>

                        <div class="col-6 text-muted">{{ __('Payment Status:') }}</div>
                        <div class="col-6 text-end fw-bold text-uppercase">{{ $purchaseInvoice->payment_status }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Produce Items Table --}}
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-items align-middle text-sm mb-0">
                <thead>
                    <tr class="small text-center">
                        <th style="width: 5%;">#</th>
                        <th class="text-start" style="width: 25%;">{{ __('Produce Description') }}</th>
                        <th style="width: 15%;">{{ __('Lot / Batch') }}</th>
                        <th style="width: 10%;">{{ __('Crates/Qty') }}</th>
                        <th style="width: 11%;">{{ __('Gross Wt') }}</th>
                        <th style="width: 11%;">{{ __('Tare Wt') }}</th>
                        <th style="width: 11%;">{{ __('Net Wt') }}</th>
                        <th style="width: 12%;">{{ __('Rate') }}</th>
                        <th class="text-end" style="width: 15%;">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseInvoice->items as $idx => $item)
                        <tr>
                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-bold">{{ $item->product->name }}</div>
                                @if($item->product->name_ur)
                                    <div class="font-urdu text-success small">{{ $item->product->name_ur }}</div>
                                @endif
                            </td>
                            <td class="text-center font-monospace small">{{ $item->lot_number }}</td>
                            <td class="text-center">{{ number_format($item->quantity, 2) }} {{ $item->unit->short_name ?? '' }}</td>
                            <td class="text-center">{{ $item->gross_weight ? number_format($item->gross_weight, 2) . ' kg' : '-' }}</td>
                            <td class="text-center text-danger">{{ $item->tare_weight ? number_format($item->tare_weight, 2) . ' kg' : '-' }}</td>
                            <td class="text-center fw-bold text-success">{{ $item->net_weight ? number_format($item->net_weight, 2) . ' kg' : number_format($item->quantity, 2) }}</td>
                            <td class="text-center">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Financial Breakdown & Summary --}}
        <div class="row g-3 mb-5">
            <div class="col-7">
                @if($purchaseInvoice->notes)
                    <div class="p-3 border rounded small text-muted mb-3">
                        <strong>{{ __('Notes / Special Instructions:') }}</strong><br>
                        {{ $purchaseInvoice->notes }}
                    </div>
                @endif
                <div class="alert alert-light border small text-muted">
                    <small>
                        * {{ __('Gross weight reflects vehicle / crate scale weight. Tare weight reflects empty crates/packaging deduction. Mandi commission is charged per market rate.') }}
                    </small>
                </div>
            </div>

            <div class="col-5">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-2">{{ __('Produce Subtotal:') }}</td>
                            <td class="text-end pe-2 fw-semibold">{{ $tenant->currency }} {{ number_format($purchaseInvoice->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-2">{{ __('Mandi Commission') }} ({{ $purchaseInvoice->commission_rate }}%):</td>
                            <td class="text-end pe-2">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->commission_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-2">{{ __('Market Fee (Chungi):') }}</td>
                            <td class="text-end pe-2">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->mandi_fee, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-2">{{ __('Freight / Labour (Mazdoori):') }}</td>
                            <td class="text-end pe-2">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->freight_charges, 2) }}</td>
                        </tr>
                        @if($purchaseInvoice->other_charges > 0)
                            <tr>
                                <td class="text-muted ps-2">{{ __('Other Charges:') }}</td>
                                <td class="text-end pe-2">+ {{ $tenant->currency }} {{ number_format($purchaseInvoice->other_charges, 2) }}</td>
                            </tr>
                        @endif
                        @if($purchaseInvoice->discount > 0)
                            <tr>
                                <td class="text-success ps-2">{{ __('Discount:') }}</td>
                                <td class="text-end pe-2 text-success">- {{ $tenant->currency }} {{ number_format($purchaseInvoice->discount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="table-light">
                            <td class="ps-2 fw-bold">{{ __('Total Mandi Payable:') }}</td>
                            <td class="text-end pe-2 fw-bold fs-6 text-primary">{{ $tenant->currency }} {{ number_format($purchaseInvoice->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-2">{{ __('Paid Amount:') }}</td>
                            <td class="text-end pe-2 fw-semibold text-success">{{ $tenant->currency }} {{ number_format($purchaseInvoice->paid_amount, 2) }}</td>
                        </tr>
                        <tr class="table-danger">
                            <td class="ps-2 fw-bold text-danger">{{ __('Due Balance:') }}</td>
                            <td class="text-end pe-2 fw-bold text-danger">{{ $tenant->currency }} {{ number_format($purchaseInvoice->due_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Signatures --}}
        <div class="row pt-4 mt-5 text-center">
            <div class="col-4">
                <div class="border-dashed pt-2 small text-muted">
                    {{ __('Prepared By (Purchaser / Driver)') }}
                </div>
            </div>
            <div class="col-4">
                <div class="border-dashed pt-2 small text-muted">
                    {{ __('Received & Inspected By (Store Incharge)') }}
                </div>
            </div>
            <div class="col-4">
                <div class="border-dashed pt-2 small text-muted">
                    {{ __('Mandi Arhti / Stall Stamp & Signature') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
