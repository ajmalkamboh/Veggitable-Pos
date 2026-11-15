<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Receipt') }} - {{ $salesInvoice->invoice_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f8f9fa;
            color: #000;
            padding: 10px;
        }
        .font-urdu {
            font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', serif;
        }
        .receipt-container {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            background-color: #fff;
            padding: 12px;
            border: 1px dashed #ccc;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .receipt-table th, .receipt-table td {
            padding: 2px 0;
            font-size: 12px;
        }
        .border-dashed-top {
            border-top: 1px dashed #000;
        }
        .border-double-bottom {
            border-bottom: 3px double #000;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                width: 100%;
                border: none;
                padding: 0;
            }
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
        }
    </style>
</head>
<body>
    <!-- Print Toolbar -->
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-success btn-sm me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            {{ __('Print Receipt') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
            {{ __('Close') }}
        </button>
    </div>

    <div class="receipt-container">
        <!-- Shop Header -->
        <div class="receipt-header">
            <h5 class="fw-bold mb-0 text-uppercase">{{ $tenant->name }}</h5>
            <div class="font-urdu text-success small">{{ __('تازہ سبزیاں اور پھل') }}</div>
            <div class="small text-muted">{{ $salesInvoice->branch->name }}</div>
            @if($tenant->address)
                <div style="font-size: 11px;">{{ $tenant->address }}</div>
            @endif
            @if($tenant->phone)
                <div style="font-size: 11px;">{{ __('Tel:') }} {{ $tenant->phone }}</div>
            @endif
        </div>

        <!-- Meta Info -->
        <div class="small mb-2" style="font-size: 11px;">
            <div class="d-flex justify-content-between">
                <span><strong>{{ __('Inv #:') }}</strong> {{ $salesInvoice->invoice_no }}</span>
                <span>{{ $salesInvoice->date->format('d/m/Y') }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span><strong>{{ __('Cashier:') }}</strong> {{ $salesInvoice->creator->name ?? 'Admin' }}</span>
                <span>{{ $salesInvoice->created_at->format('H:i') }}</span>
            </div>
            @if($salesInvoice->customer)
                <div class="d-flex justify-content-between">
                    <span><strong>{{ __('Customer:') }}</strong> {{ $salesInvoice->customer->name }}</span>
                    <span>{{ $salesInvoice->customer->phone }}</span>
                </div>
            @endif
        </div>

        <!-- Line Items Table -->
        <table class="w-100 receipt-table mb-2">
            <thead>
                <tr class="border-dashed-top border-bottom">
                    <th class="text-start" style="width: 45%;">{{ __('Item') }}</th>
                    <th class="text-center" style="width: 25%;">{{ __('Qty') }}</th>
                    <th class="text-end" style="width: 30%;">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesInvoice->items as $item)
                    <tr>
                        <td>
                            <div>{{ $item->product->name }}</div>
                            @if($item->product->name_ur)
                                <div class="font-urdu text-muted" style="font-size: 10px;">{{ $item->product->name_ur }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ number_format($item->quantity, 2) }} {{ $item->product->stockUnit->short_name ?? 'kg' }}
                            <div class="text-muted extra-small">@ {{ number_format($item->unit_price, 2) }}</div>
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format($item->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Payment Breakdown -->
        <div class="border-dashed-top pt-1 mb-2" style="font-size: 12px;">
            <div class="d-flex justify-content-between">
                <span>{{ __('Subtotal:') }}</span>
                <span>{{ $tenant->currency }} {{ number_format($salesInvoice->subtotal, 2) }}</span>
            </div>

            @if($salesInvoice->discount_amount > 0)
                <div class="d-flex justify-content-between">
                    <span>{{ __('Discount:') }}</span>
                    <span>- {{ $tenant->currency }} {{ number_format($salesInvoice->discount_amount, 2) }}</span>
                </div>
            @endif

            @if($salesInvoice->tax_amount > 0)
                <div class="d-flex justify-content-between">
                    <span>{{ __('Tax:') }}</span>
                    <span>+ {{ $tenant->currency }} {{ number_format($salesInvoice->tax_amount, 2) }}</span>
                </div>
            @endif

            <div class="d-flex justify-content-between fw-bold fs-6 border-dashed-top border-double-bottom py-1 my-1">
                <span>{{ __('GRAND TOTAL:') }}</span>
                <span>{{ $tenant->currency }} {{ number_format($salesInvoice->total_amount, 2) }}</span>
            </div>

            <div class="d-flex justify-content-between">
                <span>{{ __('Paid (') }}{{ ucfirst($salesInvoice->payment_method) }}{{ __('):') }}</span>
                <span>{{ $tenant->currency }} {{ number_format($salesInvoice->paid_amount, 2) }}</span>
            </div>

            @if($salesInvoice->change_amount > 0)
                <div class="d-flex justify-content-between">
                    <span>{{ __('Change Returned:') }}</span>
                    <span>{{ $tenant->currency }} {{ number_format($salesInvoice->change_amount, 2) }}</span>
                </div>
            @endif

            @if($salesInvoice->due_amount > 0)
                <div class="d-flex justify-content-between fw-bold text-danger">
                    <span>{{ __('Balance Due (Khata):') }}</span>
                    <span>{{ $tenant->currency }} {{ number_format($salesInvoice->due_amount, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- Footer Thank You Note -->
        <div class="text-center pt-2 border-dashed-top" style="font-size: 11px;">
            <div>{{ __('Thank you for shopping with us!') }}</div>
            <div class="font-urdu fs-6 text-success mt-1">شکریہ! دوبارہ تشریف لائیں</div>
            <div class="text-muted extra-small mt-1">{{ __('Software by Veggitable SaaS') }}</div>
        </div>
    </div>
</body>
</html>

