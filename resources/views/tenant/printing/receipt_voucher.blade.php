<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Receipt Voucher') }} - {{ $receipt_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; color: #212529; font-size: 13px; }
        .urdu-font { font-family: 'Noto Nastaliq Urdu', serif; }
        .voucher-box { width: 210mm; margin: 20px auto; background: #fff; padding: 15mm 20mm; border: 2px solid #000; }
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .voucher-box { width: 100%; margin: 0; padding: 0; border: none; }
            @page { size: A4 portrait; margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-success btn-sm me-2">
            {{ __('Print Receipt Voucher') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="voucher-box">
        <div class="row align-items-center border-bottom pb-3 mb-3">
            <div class="col-8">
                <h3 class="fw-bold text-dark text-uppercase mb-0">{{ $tenant->name }}</h3>
                <div class="urdu-font text-muted fs-6">{{ __('وصولی کی رسید / کیش و بینک ان فلو واؤچر') }}</div>
                <div class="small text-muted">{{ $branch->name ?? '' }}</div>
            </div>
            <div class="col-4 text-end">
                <h4 class="fw-bold text-success text-uppercase mb-0">{{ __('RECEIPT VOUCHER') }}</h4>
                <div><strong>{{ __('Receipt #:') }}</strong> <span class="font-monospace fw-bold">{{ $receipt_no }}</span></div>
                <div><strong>{{ __('Date:') }}</strong> {{ $date ? \Carbon\Carbon::parse($date)->format('d F Y') : now()->format('d F Y') }}</div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-8">
                <div class="p-2 border rounded">
                    <strong>{{ __('Received With Thanks From (ادا کنندہ):') }}</strong> {{ $payer }}
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 border rounded">
                    <strong>{{ __('Payment Mode:') }}</strong> {{ ucfirst($payment_method) }}
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 border rounded">
                    <strong>{{ __('Against Sales Invoice #:') }}</strong> {{ $invoice_no }}
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 border rounded">
                    <strong>{{ __('Reference / Cheque #:') }}</strong> {{ $transaction_reference ?? 'Counter Cash Till' }}
                </div>
            </div>
        </div>

        <div class="card bg-light border-0 mb-4 p-3 text-center">
            <div class="text-muted small text-uppercase">{{ __('Total Amount Received') }}</div>
            <h2 class="fw-bold text-success my-1 font-monospace">Rs. {{ number_format($amount, 2) }}</h2>
            <div class="small fw-semibold text-dark">{{ __('Amount in Words: ') }} <span class="fst-italic">{{ $amount_in_words }}</span></div>
        </div>

        <div class="row mt-5 pt-4 text-center">
            <div class="col-6">
                <div class="urdu-font text-muted small text-start">
                    {{ __('شکریہ! وصول شدہ رقم کاؤنٹر ریکارڈ میں درج کر لی گئی ہے۔') }}
                </div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Payer Signature') }}</div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Cashier Signature') }} ({{ $cashier }})</div>
            </div>
        </div>
    </div>
</body>
</html>

