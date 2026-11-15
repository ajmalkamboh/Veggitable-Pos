<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Supplier Statement') }} - {{ $supplier->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; color: #212529; font-size: 13px; }
        .urdu-font { font-family: 'Noto Nastaliq Urdu', serif; }
        .a4-page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 20mm; border: 1px solid #dee2e6; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .a4-page { width: 100%; margin: 0; padding: 0; border: none; box-shadow: none; }
            @page { size: A4 portrait; margin: 15mm; }
        }
    </style>
</head>
<body>
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
            {{ __('Print Supplier Statement') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-4 mb-4">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-1">{{ $tenant->name }}</h3>
                <div class="urdu-font text-success fs-6 mb-1">{{ __('آڑھتی و بیوپاری کھاتہ / سپلائر لیجر') }}</div>
                @if($tenant->address)<div class="text-muted small">{{ $tenant->address }}</div>@endif
                @if($tenant->phone)<div class="text-muted small">{{ __('Phone:') }} {{ $tenant->phone }}</div>@endif
            </div>
            <div class="col-5 text-end">
                <h4 class="text-primary text-uppercase fw-bold mb-1">{{ __('SUPPLIER STATEMENT') }}</h4>
                <div><strong>{{ __('Date Generated:') }}</strong> {{ now()->format('d F Y') }}</div>
                @if($date_from || $date_to)
                    <div class="small text-muted">
                        {{ __('Period:') }} {{ $date_from ? \Carbon\Carbon::parse($date_from)->format('d/m/Y') : 'Start' }} 
                        - {{ $date_to ? \Carbon\Carbon::parse($date_to)->format('d/m/Y') : 'Present' }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Supplier Summary -->
        <div class="card bg-light border-0 mb-4">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-6">
                        <h6 class="fw-bold text-muted small text-uppercase mb-1">{{ __('Mandi Commission Agent / Supplier:') }}</h6>
                        <h5 class="fw-bold mb-1">{{ $supplier->name }}</h5>
                        <div><strong>{{ __('Contact Person:') }}</strong> {{ $supplier->contact_person ?? '-' }}</div>
                        <div><strong>{{ __('Phone:') }}</strong> {{ $supplier->phone ?? '-' }}</div>
                    </div>
                    <div class="col-6 text-end">
                        <h6 class="fw-bold text-muted small text-uppercase mb-1">{{ __('Mandi Settlement Summary:') }}</h6>
                        <div>{{ __('Total Procurement:') }} <span class="font-monospace fw-bold">Rs. {{ number_format($total_purchases, 2) }}</span></div>
                        <div>{{ __('Total Payments Made:') }} <span class="font-monospace fw-bold text-success">Rs. {{ number_format($total_paid, 2) }}</span></div>
                        <div class="small text-muted">{{ __('Total Commission Deducted:') }} Rs. {{ number_format($total_commission, 2) }}</div>
                        <div class="fs-5 mt-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger">
                                {{ __('Net Payable Balance: Rs. ') }}{{ number_format($current_payable, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procurement Ledger Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Bill #') }}</th>
                    <th>{{ __('Aarthi Slip #') }}</th>
                    <th class="text-end">{{ __('Produce Subtotal') }}</th>
                    <th class="text-end">{{ __('Commission') }}</th>
                    <th class="text-end">{{ __('Total Bill') }}</th>
                    <th class="text-end">{{ __('Paid') }}</th>
                    <th class="text-end">{{ __('Due Balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                    <tr>
                        <td>{{ $p->date ? $p->date->format('d/m/Y') : '-' }}</td>
                        <td class="font-monospace fw-bold">{{ $p->invoice_no }}</td>
                        <td class="font-monospace text-muted">{{ $p->supplier_invoice_no ?? '-' }}</td>
                        <td class="text-end font-monospace">{{ number_format($p->subtotal, 2) }}</td>
                        <td class="text-end font-monospace text-muted">{{ number_format($p->commission_amount, 2) }}</td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($p->total_amount, 2) }}</td>
                        <td class="text-end font-monospace text-success">{{ number_format($p->paid_amount, 2) }}</td>
                        <td class="text-end font-monospace text-danger fw-bold">{{ number_format($p->due_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">{{ __('No mandi procurement invoices found for this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <div class="urdu-font text-muted small">
                    {{ __('یہ کمپیوٹرائزڈ آڑھتی لیجر تصدیق شدہ ہے۔ حساب کی تفصیلی جانچ کے بعد ادائیگی کی جائے گی۔') }}
                </div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Supplier / Aarthi Signature') }}</div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Manager Signature') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

