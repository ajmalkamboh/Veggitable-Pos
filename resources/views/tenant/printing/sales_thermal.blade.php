<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Receipt') }} - {{ $receiptData['invoice_no'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f4f6f8;
            color: #000;
            padding: 20px;
        }
        .urdu-font {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        .thermal-ticket {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 12px 14px;
            border: 1px dashed #999;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            font-size: 12px;
            line-height: 1.35;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .shop-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .urdu-tagline {
            font-size: 13px;
            margin: 2px 0;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 11px;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        .item-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            font-size: 11px;
            text-align: left;
        }
        [dir="rtl"] .item-table th {
            text-align: right;
        }
        .item-table td {
            padding: 3px 0;
            font-size: 11px;
            vertical-align: top;
        }
        .totals-section {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 6px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .net-total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 4px 0;
        }
        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 11px;
        }
        .actions-toolbar {
            text-align: center;
            margin-bottom: 15px;
        }
        .btn-print {
            background-color: #2e7d32;
            color: #fff;
            border: none;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-close {
            background-color: #757575;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 8px;
        }
        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .actions-toolbar {
                display: none !important;
            }
            .thermal-ticket {
                width: 100%;
                border: none;
                box-shadow: none;
                padding: 2mm;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions-toolbar">
        <button onclick="window.print()" class="btn-print">{{ __('Print 80mm Receipt') }}</button>
        <button onclick="window.close()" class="btn-close">{{ __('Close') }}</button>
    </div>

    <div class="thermal-ticket">
        <div class="header">
            @if($tenant->logo_path)
                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo" style="max-height: 45px; margin-bottom: 4px;">
            @endif
            <div class="shop-name">{{ $tenant->name }}</div>
            <div class="urdu-font urdu-tagline">{{ __('تازہ سبزی اور پھل مارٹ') }}</div>
            <div style="font-size: 11px;">{{ $receiptData['branch_name'] }}</div>
            @if($receiptData['branch_address'])
                <div style="font-size: 10px;">{{ $receiptData['branch_address'] }}</div>
            @endif
            @if($receiptData['branch_phone'])
                <div style="font-size: 10px;">{{ __('Phone:') }} {{ $receiptData['branch_phone'] }}</div>
            @endif
        </div>

        <div class="meta-row">
            <span><strong>{{ __('Inv #:') }}</strong> {{ $receiptData['invoice_no'] }}</span>
            <span>{{ $receiptData['date'] }} {{ $receiptData['time'] }}</span>
        </div>
        <div class="meta-row">
            <span><strong>{{ __('Cashier:') }}</strong> {{ $receiptData['cashier_name'] }}</span>
            <span><strong>{{ __('Customer:') }}</strong> {{ $receiptData['customer_name'] }}</span>
        </div>

        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 45%;">{{ __('Item') }}</th>
                    <th style="text-align: right; width: 25%;">{{ __('Qty x Rate') }}</th>
                    <th style="text-align: right; width: 30%;">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receiptData['items'] as $item)
                    <tr>
                        <td>
                            <div>{{ $item['name'] }}</div>
                            @if(!empty($item['urdu_name']))
                                <div class="urdu-font" style="font-size: 10px; color: #333;">{{ $item['urdu_name'] }}</div>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            {{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}<br>
                            @ {{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ number_format($item['subtotal'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span>{{ __('Subtotal:') }}</span>
                <span>{{ number_format($receiptData['subtotal'], 2) }}</span>
            </div>
            @if($receiptData['discount_amount'] > 0)
                <div class="total-row">
                    <span>{{ __('Discount:') }}</span>
                    <span>-{{ number_format($receiptData['discount_amount'], 2) }}</span>
                </div>
            @endif
            @if($receiptData['tax_amount'] > 0)
                <div class="total-row">
                    <span>{{ __('Tax / GST:') }}</span>
                    <span>+{{ number_format($receiptData['tax_amount'], 2) }}</span>
                </div>
            @endif
            <div class="net-total-row">
                <span>{{ __('NET TOTAL:') }}</span>
                <span>Rs. {{ number_format($receiptData['total_amount'], 2) }}</span>
            </div>
            <div class="total-row">
                <span>{{ __('Paid (') }}{{ ucfirst($receiptData['payment_method']) }}{{ __('):') }}</span>
                <span>{{ number_format($receiptData['paid_amount'], 2) }}</span>
            </div>
            @if($receiptData['change_amount'] > 0)
                <div class="total-row">
                    <span>{{ __('Change Returned:') }}</span>
                    <span>{{ number_format($receiptData['change_amount'], 2) }}</span>
                </div>
            @endif
            @if($receiptData['due_amount'] > 0)
                <div class="total-row" style="color: #b71c1c; font-weight: bold;">
                    <span>{{ __('Bill Due Amount:') }}</span>
                    <span>{{ number_format($receiptData['due_amount'], 2) }}</span>
                </div>
            @endif
            @if($receiptData['customer_balance'] > 0)
                <div class="total-row" style="border-top: 1px dotted #000; padding-top: 2px;">
                    <span>{{ __('Customer Balance:') }}</span>
                    <span>Rs. {{ number_format($receiptData['customer_balance'], 2) }}</span>
                </div>
            @endif
        </div>

        <div class="footer">
            <div class="urdu-font" style="font-size: 13px; font-weight: bold;">{{ __('تشریف لانے کا شکریہ! تازہ مال روزانہ دستیاب') }}</div>
            <div style="font-size: 10px; margin-top: 2px;">{{ __('Software by Veggitable SaaS System') }}</div>
        </div>
    </div>
</body>
</html>

