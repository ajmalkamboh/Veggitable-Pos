<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Stock Valuation & Audit Report') }} - {{ $tenant->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; color: #212529; font-size: 12px; }
        .urdu-font { font-family: 'Noto Nastaliq Urdu', serif; }
        .a4-page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 15mm 20mm; border: 1px solid #dee2e6; }
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .a4-page { width: 100%; margin: 0; padding: 0; border: none; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
            {{ __('Print Stock Audit Sheet') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-3 mb-3">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-0">{{ $tenant->name }}</h3>
                <div class="urdu-font text-success fs-6">{{ __('اسٹاک آڈٹ و مال کی کل مالیت رپورٹ') }}</div>
                <div class="small text-muted">{{ $branch ? $branch->name : __('All Branches / Consolidated') }}</div>
            </div>
            <div class="col-5 text-end">
                <h5 class="fw-bold text-uppercase mb-1">{{ __('INVENTORY VALUATION SHEET') }}</h5>
                <div><strong>{{ __('Report Generated:') }}</strong> {{ $report_date }}</div>
                <div><strong>{{ __('Total SKUs Tracked:') }}</strong> {{ $total_items }}</div>
            </div>
        </div>

        <!-- Summary KPI Row -->
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Total Physical Quantity') }}</span>
                    <h5 class="fw-bold mb-0 font-monospace">{{ number_format($total_quantity, 2) }} kg</h5>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Inventory Valuation (Cost Basis)') }}</span>
                    <h5 class="fw-bold text-primary mb-0 font-monospace">Rs. {{ number_format($total_valuation_cost, 2) }}</h5>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Inventory Valuation (Retail Basis)') }}</span>
                    <h5 class="fw-bold text-success mb-0 font-monospace">Rs. {{ number_format($total_valuation_retail, 2) }}</h5>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <table class="table table-bordered table-sm align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>{{ __('Produce Item / Code') }}</th>
                    <th>{{ __('Outlet') }}</th>
                    <th class="text-end" style="width: 14%;">{{ __('Current Stock') }}</th>
                    <th class="text-end" style="width: 12%;">{{ __('Reorder') }}</th>
                    <th class="text-end" style="width: 13%;">{{ __('Cost (Rs.)') }}</th>
                    <th class="text-end" style="width: 13%;">{{ __('Selling (Rs.)') }}</th>
                    <th class="text-end" style="width: 15%;">{{ __('Total Value (Cost)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $idx => $s)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <span class="fw-bold text-dark">{{ $s->name }}</span>
                            @if(!empty($s->urdu_name))
                                <span class="urdu-font text-muted small ms-1">({{ $s->urdu_name }})</span>
                            @endif
                            <div class="font-monospace text-muted small" style="font-size: 10px;">{{ $s->code }}</div>
                        </td>
                        <td>{{ $s->branch_name }}</td>
                        <td class="text-end font-monospace fw-bold {{ $s->quantity <= $s->reorder_level ? 'text-danger' : 'text-dark' }}">
                            {{ number_format($s->quantity, 2) }} kg
                        </td>
                        <td class="text-end font-monospace text-muted">{{ number_format($s->reorder_level, 2) }} kg</td>
                        <td class="text-end font-monospace">{{ number_format($s->cost_price, 2) }}</td>
                        <td class="text-end font-monospace">{{ number_format($s->selling_price, 2) }}</td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($s->quantity * $s->cost_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">{{ __('No inventory records found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Verification Signatures -->
        <div class="row mt-5 pt-4 text-center">
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Physical Stock Auditor') }}</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Warehouse / Cold Room Incharge') }}</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('General Manager Approval') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

