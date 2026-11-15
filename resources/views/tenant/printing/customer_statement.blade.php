<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Customer Statement') }} - {{ $customer->name }}</title>
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
            {{ __('Print Statement of Account') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-4 mb-4">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-1">{{ $tenant->name }}</h3>
                <div class="urdu-font text-success fs-6 mb-1">{{ __('گاہک کا کھاتہ / اسٹیٹمنٹ آف اکاؤنٹ') }}</div>
                @if($tenant->address)<div class="text-muted small">{{ $tenant->address }}</div>@endif
                @if($tenant->phone)<div class="text-muted small">{{ __('Phone:') }} {{ $tenant->phone }}</div>@endif
            </div>
            <div class="col-5 text-end">
                <h4 class="text-primary text-uppercase fw-bold mb-1">{{ __('STATEMENT OF ACCOUNT') }}</h4>
                <div><strong>{{ __('Date Generated:') }}</strong> {{ now()->format('d F Y') }}</div>
                @if($date_from || $date_to)
                    <div class="small text-muted">
                        {{ __('Period:') }} {{ $date_from ? \Carbon\Carbon::parse($date_from)->format('d/m/Y') : 'Start' }} 
                        - {{ $date_to ? \Carbon\Carbon::parse($date_to)->format('d/m/Y') : 'Present' }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Customer Summary -->
        <div class="card bg-light border-0 mb-4">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-6">
                        <h6 class="fw-bold text-muted small text-uppercase mb-1">{{ __('Customer Information:') }}</h6>
                        <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                        <div><strong>{{ __('Phone:') }}</strong> {{ $customer->phone ?? '-' }}</div>
                        <div><strong>{{ __('Address:') }}</strong> {{ $customer->address ?? '-' }}</div>
                    </div>
                    <div class="col-6 text-end">
                        <h6 class="fw-bold text-muted small text-uppercase mb-1">{{ __('Account Summary:') }}</h6>
                        <div>{{ __('Total Invoiced:') }} <span class="font-monospace fw-bold">Rs. {{ number_format($total_billed, 2) }}</span></div>
                        <div>{{ __('Total Recovered:') }} <span class="font-monospace fw-bold text-success">Rs. {{ number_format($total_paid, 2) }}</span></div>
                        <div class="fs-5 mt-2">
                            <span class="badge bg-danger-subtle text-danger border border-danger">
                                {{ __('Current Balance Due: Rs. ') }}{{ number_format($outstanding_balance, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices & Transactions Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Invoice #') }}</th>
                    <th>{{ __('Outlet') }}</th>
                    <th class="text-end">{{ __('Bill Amount') }}</th>
                    <th class="text-end">{{ __('Paid') }}</th>
                    <th class="text-end">{{ __('Due Amount') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td>{{ $inv->date ? $inv->date->format('d/m/Y') : '-' }}</td>
                        <td class="font-monospace fw-bold">{{ $inv->invoice_no }}</td>
                        <td>{{ $inv->branch->name ?? '-' }}</td>
                        <td class="text-end font-monospace">{{ number_format($inv->total_amount, 2) }}</td>
                        <td class="text-end font-monospace text-success">{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-end font-monospace text-danger fw-bold">{{ number_format($inv->due_amount, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $inv->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($inv->payment_status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No sales transactions found for this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <div class="urdu-font text-muted small">
                    {{ __('یہ کمپیوٹرائزڈ کھاتہ تصدیق شدہ ہے۔ کسی بھی فرق کی صورت میں فوری رابطہ کریں۔') }}
                </div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Customer Signature') }}</div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Accountant Signature') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

