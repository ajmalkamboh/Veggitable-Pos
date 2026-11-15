<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Mandi Procurement Bill') }} - {{ $invoice->invoice_no }}</title>
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
            {{ __('Print Mandi Purchase Bill') }}
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">{{ __('Close') }}</button>
    </div>

    <div class="a4-page">
        <!-- Header -->
        <div class="row align-items-center border-bottom pb-4 mb-4">
            <div class="col-7">
                <h3 class="fw-bold text-dark text-uppercase mb-1">{{ $tenant->name }}</h3>
                <div class="urdu-font text-success fs-6 mb-1">{{ __('سبزی منڈی خریداری بل / واؤچر') }}</div>
                <div class="text-muted small">{{ $branch->name ?? '' }}</div>
                @if($tenant->address)
                    <div class="text-muted small">{{ $tenant->address }}</div>
                @endif
            </div>
            <div class="col-5 text-end">
                <h4 class="text-primary text-uppercase fw-bold mb-1">{{ __('MANDI PURCHASE BILL') }}</h4>
                <div><strong>{{ __('Bill #:') }}</strong> <span class="font-monospace fw-bold">{{ $invoice->invoice_no }}</span></div>
                <div><strong>{{ __('Date:') }}</strong> {{ $invoice->date ? $invoice->date->format('d F Y') : '-' }}</div>
                @if($invoice->supplier_invoice_no)
                    <div><strong>{{ __('Aarthi Slip #:') }}</strong> {{ $invoice->supplier_invoice_no }}</div>
                @endif
                <div><strong>{{ __('Status:') }}</strong> <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span></div>
            </div>
        </div>

        <!-- Supplier & Mandi Details -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">{{ __('Supplier / Commission Agent (آڑھتی / بیوپاری):') }}</h6>
                    <h5 class="fw-bold mb-1">{{ $supplier->name ?? 'Direct Farmer' }}</h5>
                    @if($supplier)
                        <div><strong>{{ __('Contact Person:') }}</strong> {{ $supplier->contact_person ?? '-' }}</div>
                        <div><strong>{{ __('Phone:') }}</strong> {{ $supplier->phone ?? '-' }}</div>
                        <div class="text-danger small mt-1">
                            <strong>{{ __('Current Payable Balance:') }}</strong> Rs. {{ number_format($supplier->current_balance, 2) }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">{{ __('Procurement Lot Details:') }}</h6>
                    <div><strong>{{ __('Receiving Outlet:') }}</strong> {{ $branch->name ?? '-' }}</div>
                    <div><strong>{{ __('Purchaser / Incharge:') }}</strong> {{ $invoice->creator->name ?? 'Admin' }}</div>
                    @if($invoice->notes)
                        <div><strong>{{ __('Notes:') }}</strong> {{ $invoice->notes }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Purchase Line Items -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>{{ __('Vegetable / Fruit Lot') }}</th>
                    <th class="text-end" style="width: 15%;">{{ __('Scale Qty') }}</th>
                    <th class="text-end" style="width: 15%;">{{ __('Unit Rate') }}</th>
                    <th class="text-end" style="width: 20%;">{{ __('Produce Total (Rs.)') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item->product->name ?? 'Produce Item' }}</div>
                            @if(!empty($item->product->urdu_name))
                                <div class="urdu-font text-muted small">{{ $item->product->urdu_name }}</div>
                            @endif
                        </td>
                        <td class="text-end font-monospace">{{ number_format($item->quantity, 2) }} {{ $item->unit->code ?? 'kg' }}</td>
                        <td class="text-end font-monospace">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end fw-bold font-monospace">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">{{ __('Lot items summarized below.') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end fw-bold">{{ __('Produce Net Value:') }}</td>
                    <td class="text-end fw-bold font-monospace">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->commission_amount > 0)
                    <tr>
                        <td colspan="4" class="text-end text-muted">{{ __('Mandi Commission Agent Fee:') }}</td>
                        <td class="text-end font-monospace">+{{ number_format($invoice->commission_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->mandi_fee > 0)
                    <tr>
                        <td colspan="4" class="text-end text-muted">{{ __('Market Committee / Chungi Tax:') }}</td>
                        <td class="text-end font-monospace">+{{ number_format($invoice->mandi_fee, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->freight_charges > 0)
                    <tr>
                        <td colspan="4" class="text-end text-muted">{{ __('Mazdoori & Carriage Freight:') }}</td>
                        <td class="text-end font-monospace">+{{ number_format($invoice->freight_charges, 2) }}</td>
                    </tr>
                @endif
                <tr class="table-primary fs-6">
                    <td colspan="4" class="text-end fw-bold text-uppercase">{{ __('Total Procurement Bill:') }}</td>
                    <td class="text-end fw-bold font-monospace">Rs. {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end text-success fw-bold">{{ __('Paid Amount:') }}</td>
                    <td class="text-end text-success fw-bold font-monospace">Rs. {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                @if($invoice->due_amount > 0)
                    <tr class="table-danger">
                        <td colspan="4" class="text-end text-danger fw-bold">{{ __('Payable Balance Due:') }}</td>
                        <td class="text-end text-danger fw-bold font-monospace">Rs. {{ number_format($invoice->due_amount, 2) }}</td>
                    </tr>
                @endif
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <div class="urdu-font text-muted small">
                    {{ __('نوٹ: منڈی کے تمام وزن اور کمیشن کٹوتیوں کی تصدیق کے بعد بل کمپیوٹر میں درج کیا گیا ہے۔') }}
                </div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Aarthi / Supplier Signature') }}</div>
            </div>
            <div class="col-3 text-center">
                <div style="border-bottom: 1px solid #000; margin-top: 40px; margin-bottom: 5px;"></div>
                <div class="small fw-semibold">{{ __('Store Receiver Signature') }}</div>
            </div>
        </div>
    </div>
</body>
</html>

