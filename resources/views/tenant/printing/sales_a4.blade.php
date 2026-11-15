<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Sales Tax Invoice') }} - {{ $invoice->invoice_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-size: 13px;
        }
        .urdu-font {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            padding: 20mm;
            border: 1px solid #dee2e6;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }
        .invoice-title {
            letter-spacing: 1px;
            font-weight: 800;
        }
        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
            <i class="bi bi-printer"></i> {{ __('Print A4 Invoice') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-4 mb-4">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-1">{{ $tenant->name }}</h3>
                <div class="urdu-font text-success fs-6 mb-1">{{ __('تازہ سبزی و فروٹ ہول سیل اینڈ ریٹیل مارٹ') }}</div>
                <div class="text-muted small">{{ $branch->name ?? '' }}</div>
                @if($tenant->address)
                    <div class="text-muted small">{{ $tenant->address }}</div>
                @endif
                @if($tenant->phone)
                    <div class="text-muted small">{{ __('Phone:') }} {{ $tenant->phone }} | {{ __('Email:') }} {{ $tenant->email }}</div>
                @endif
            </div>
            <div class="col-5 text-end">
                <h4 class="invoice-title text-primary text-uppercase mb-1">{{ __('SALES INVOICE') }}</h4>
                <div class="urdu-font text-muted fs-6 mb-2">{{ __('سیلز انوائس / بل') }}</div>
                <div><strong>{{ __('Invoice #:') }}</strong> <span class="font-monospace fw-bold">{{ $invoice->invoice_no }}</span></div>
                <div><strong>{{ __('Date:') }}</strong> {{ $invoice->date ? $invoice->date->format('d F Y') : '-' }}</div>
                <div><strong>{{ __('Payment Status:') }}</strong> 
                    <span class="badge {{ $invoice->payment_status === 'paid' ? 'bg-success' : ($invoice->payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ ucfirst($invoice->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Bill To -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">{{ __('Billed To (خریدار / پارٹی):') }}</h6>
                    <h5 class="fw-bold mb-1">{{ $customer->name ?? __('Walk-in Retail Customer') }}</h5>
                    @if($customer)
                        @if($customer->phone)
                            <div><strong>{{ __('Phone:') }}</strong> {{ $customer->phone }}</div>
                        @endif
                        @if($customer->address)
                            <div class="text-muted small">{{ $customer->address }}</div>
                        @endif
                        <div class="text-danger small mt-1">
                            <strong>{{ __('Outstanding Ledger Balance:') }}</strong> Rs. {{ number_format($customer->current_balance, 2) }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">{{ __('Transaction Details:') }}</h6>
                    <div><strong>{{ __('Cashier / Biller:') }}</strong> {{ $invoice->creator->name ?? 'Admin' }}</div>
                    <div><strong>{{ __('Payment Method:') }}</strong> {{ ucfirst($invoice->payment_method ?? 'Cash') }}</div>
                    @if($invoice->notes)
                        <div><strong>{{ __('Notes:') }}</strong> {{ $invoice->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>{{ __('Item Description / نام جنس') }}</th>
                    <th class="text-end" style="width: 15%;">{{ __('Quantity') }}</th>
                    <th class="text-end" style="width: 15%;">{{ __('Unit Rate') }}</th>
                    <th class="text-end" style="width: 12%;">{{ __('Discount') }}</th>
                    <th class="text-end" style="width: 18%;">{{ __('Net Total (Rs.)') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->product->name ?? 'Produce Item' }}</div>
                            @if(!empty($item->product->urdu_name))
                                <div class="urdu-font text-muted small">{{ $item->product->urdu_name }}</div>
                            @endif
                        </td>
                        <td class="text-end font-monospace">{{ number_format($item->quantity, 2) }} {{ $item->product->stockUnit->code ?? 'kg' }}</td>
                        <td class="text-end font-monospace">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end text-danger font-monospace">{{ number_format($item->discount_amount, 2) }}</td>
                        <td class="text-end fw-bold font-monospace">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end fw-bold">{{ __('Subtotal:') }}</td>
                    <td class="text-end fw-bold font-monospace">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                    <tr>
                        <td colspan="5" class="text-end text-danger fw-bold">{{ __('Discount:') }}</td>
                        <td class="text-end text-danger fw-bold font-monospace">-{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->tax_amount > 0)
                    <tr>
                        <td colspan="5" class="text-end fw-bold">{{ __('Tax / GST:') }}</td>
                        <td class="text-end fw-bold font-monospace">+{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="table-primary fs-6">
                    <td colspan="5" class="text-end fw-bold text-uppercase">{{ __('Total Payable Amount:') }}</td>
                    <td class="text-end fw-bold font-monospace">Rs. {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end text-success fw-bold">{{ __('Paid Amount:') }}</td>
                    <td class="text-end text-success fw-bold font-monospace">Rs. {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                @if($invoice->due_amount > 0)
                    <tr class="table-danger">
                        <td colspan="5" class="text-end text-danger fw-bold">{{ __('Remaining Balance Due:') }}</td>
                        <td class="text-end text-danger fw-bold font-monospace">Rs. {{ number_format($invoice->due_amount, 2) }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>

        <!-- Signatures & Terms -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <h6 class="fw-bold small text-muted text-uppercase mb-2">{{ __('Terms & Conditions:') }}</h6>
                <ul class="text-muted small ps-3 mb-0">
                    <li>{{ __('Perishable vegetables and fruits cannot be returned after leaving the counter.') }}</li>
                    <li>{{ __('Credit bills must be settled within the agreed credit payment term.') }}</li>
                    <li class="urdu-font">{{ __('خریدا ہوا تازہ مال کاؤنٹر چھوڑنے کے بعد واپس نہیں ہوگا۔') }}</li>
                </ul>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Customer Signature') }}</div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Authorized Signature') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

