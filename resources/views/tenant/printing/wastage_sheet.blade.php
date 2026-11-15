<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Produce Wastage & Damage Audit Sheet') }} - {{ $tenant->name }}</title>
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
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-danger btn-sm me-2">
            {{ __('Print Spoilage Audit Sheet') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-3 mb-3">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-0">{{ $tenant->name }}</h3>
                <div class="urdu-font text-danger fs-6">{{ __('سبزی و پھل ضیاع / خراب مال و نقصان کی تفصیل') }}</div>
                <div class="small text-muted">{{ $branch ? $branch->name : __('All Outlets') }}</div>
            </div>
            <div class="col-5 text-end">
                <h5 class="fw-bold text-danger text-uppercase mb-1">{{ __('PRODUCE WASTAGE & CULL AUDIT') }}</h5>
                <div><strong>{{ __('Date Generated:') }}</strong> {{ $report_date }}</div>
                @if($from || $to)
                    <div class="small text-muted">
                        {{ __('Period:') }} {{ $from ?? 'Start' }} - {{ $to ?? 'Present' }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Summary KPI Row -->
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Total Wastage Vouchers') }}</span>
                    <h5 class="fw-bold mb-0 font-monospace">{{ $total_records }}</h5>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Total Produce Lost (Weight)') }}</span>
                    <h5 class="fw-bold text-warning mb-0 font-monospace">{{ number_format($total_loss_qty, 2) }} kg</h5>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 border rounded bg-light text-center">
                    <span class="text-muted small">{{ __('Total Financial Spoilage Loss') }}</span>
                    <h5 class="fw-bold text-danger mb-0 font-monospace">Rs. {{ number_format($total_loss_cost, 2) }}</h5>
                </div>
            </div>
        </div>

        <!-- Wastage Vouchers Table -->
        <table class="table table-bordered table-sm align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Voucher #') }}</th>
                    <th>{{ __('Outlet') }}</th>
                    <th>{{ __('Reason / نوعیت نقصان') }}</th>
                    <th class="text-end">{{ __('Lost Qty') }}</th>
                    <th class="text-end">{{ __('Financial Loss (Rs.)') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>{{ $rec->date ? $rec->date->format('d/m/Y') : '-' }}</td>
                        <td class="font-monospace fw-bold">{{ $rec->wastage_no }}</td>
                        <td>{{ $rec->branch->name ?? '-' }}</td>
                        <td>{{ $rec->reason_label }}</td>
                        <td class="text-end font-monospace">{{ number_format($rec->total_quantity, 2) }} kg</td>
                        <td class="text-end font-monospace fw-bold text-danger">{{ number_format($rec->total_loss_cost, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $rec->status === 'approved' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($rec->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No produce wastage records found for this period.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="row mt-5 pt-4 text-center">
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Cull Sorting Staff') }}</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Inventory Manager') }}</div>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Owner / Authorized Sign') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

