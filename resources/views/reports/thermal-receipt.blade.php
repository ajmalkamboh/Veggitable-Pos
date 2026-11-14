<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>{{ __('shop.thermal_receipt') }}</title>
  <style>
    @page {
      margin: 0;
      size: 80mm auto;
    }
    body {
      font-family: 'Noto Sans Arabic', 'Noto Nastaliq Urdu', Arial, sans-serif;
      font-size: 12px;
      line-height: 1.4;
      margin: 0;
      padding: 10px;
      color: #000;
      background: #fff;
      direction: {{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }};
      text-align: {{ app()->getLocale() === 'ur' ? 'right' : 'left' }};
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 10px;
      border-bottom: 1px dashed #000;
      padding-bottom: 8px;
    }
    .receipt-title {
      font-size: 16px;
      font-weight: bold;
      margin: 0;
    }
    .receipt-meta {
      font-size: 10px;
      color: #333;
    }
    .receipt-table {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0;
    }
    .receipt-table th {
      border-bottom: 1px solid #000;
      font-size: 11px;
      padding: 4px 2px;
      text-align: {{ app()->getLocale() === 'ur' ? 'right' : 'left' }};
    }
    .receipt-table td {
      padding: 4px 2px;
      border-bottom: 1px dotted #ccc;
    }
    .text-end {
      text-align: {{ app()->getLocale() === 'ur' ? 'left' : 'right' }};
    }
    .receipt-totals {
      border-top: 1px dashed #000;
      padding-top: 6px;
      margin-top: 6px;
    }
    .total-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 3px;
    }
    .total-grand {
      font-size: 14px;
      font-weight: bold;
      border-top: 1px solid #000;
      padding-top: 4px;
    }
    .receipt-footer {
      text-align: center;
      margin-top: 12px;
      border-top: 1px dashed #000;
      padding-top: 8px;
      font-size: 10px;
    }
    @media print {
      body {
        width: 80mm;
      }
    }
  </style>
</head>
<body>
  <div class="receipt-header">
    <h2 class="receipt-title">{{ $tenant->name ?? 'المدینہ تازہ سبزیاں' }}</h2>
    <div class="receipt-meta">{{ $branch->name ?? 'مین برانچ، سبزی منڈی' }}</div>
    <div class="receipt-meta">{{ $tenant->phone ?? '+92 300 1234567' }}</div>
    <div class="receipt-meta">{{ now()->format('Y-m-d h:i A') }} | POS-{{ rand(100, 999) }}</div>
  </div>

  <table class="receipt-table">
    <thead>
      <tr>
        <th>{{ __('shop.vegetables') }}</th>
        <th>{{ __('shop.unit') }}</th>
        <th class="text-end">{{ __('shop.price_per_kg') }}</th>
        <th class="text-end">{{ __('shop.total_amount') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>تازہ ٹماٹر (Fresh Tomatoes)</td>
        <td>2.50 kg</td>
        <td class="text-end">120.00</td>
        <td class="text-end">300.00</td>
      </tr>
      <tr>
        <td>پیاز درجہ اول (Dry Onions)</td>
        <td>5.00 kg</td>
        <td class="text-end">160.00</td>
        <td class="text-end">800.00</td>
      </tr>
      <tr>
        <td>آلو سفید (White Potatoes)</td>
        <td>10.00 kg</td>
        <td class="text-end">80.00</td>
        <td class="text-end">800.00</td>
      </tr>
    </tbody>
  </table>

  <div class="receipt-totals">
    <div class="total-row">
      <span>{{ __('shop.total_amount') }}:</span>
      <span>PKR 1,900.00</span>
    </div>
    <div class="total-row">
      <span>{{ __('shop.discount') }}:</span>
      <span>PKR 50.00</span>
    </div>
    <div class="total-row total-grand">
      <span>{{ __('shop.net_payable') }}:</span>
      <span>PKR 1,850.00</span>
    </div>
    <div class="total-row" style="font-size: 11px; margin-top: 4px;">
      <span>{{ __('shop.cash_received') }}:</span>
      <span>PKR 2,000.00</span>
    </div>
    <div class="total-row" style="font-size: 11px;">
      <span>{{ __('shop.change_return') }}:</span>
      <span>PKR 150.00</span>
    </div>
  </div>

  <div class="receipt-footer">
    <p style="margin: 0;">خریداری کا شکریہ! فروخت شدہ مال واپس نہیں ہوگا</p>
    <p style="margin: 2px 0 0;">Thank you for shopping with us! Powered by Veggitable SaaS</p>
  </div>
</body>
</html>
