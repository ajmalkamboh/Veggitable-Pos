<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $salesInvoice->invoice_no }} - {{ $tenant->name }}</title>
  <style>
    @page {
      margin: 12mm 15mm;
      size: a4 portrait;
    }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 11px;
      line-height: 1.4;
      color: #212529;
      background: #ffffff;
      margin: 0;
      padding: 0;
    }
    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
      border-bottom: 2px solid #0d6efd;
      padding-bottom: 10px;
    }
    .header-table td {
      vertical-align: top;
    }
    .shop-title {
      font-size: 18px;
      font-weight: bold;
      color: #0d6efd;
      margin: 0 0 4px 0;
    }
    .shop-meta {
      font-size: 9px;
      color: #6c757d;
      line-height: 1.3;
    }
    .invoice-title {
      font-size: 14px;
      font-weight: bold;
      text-transform: uppercase;
      color: #198754;
      text-align: right;
      margin: 0 0 4px 0;
    }
    .invoice-meta {
      font-size: 10px;
      text-align: right;
      color: #333333;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 4px;
    }
    .info-table td {
      padding: 8px 12px;
      vertical-align: top;
      width: 50%;
    }
    .info-label {
      font-size: 9px;
      font-weight: bold;
      text-transform: uppercase;
      color: #6c757d;
      margin-bottom: 2px;
    }
    .info-val {
      font-size: 12px;
      font-weight: bold;
      color: #212529;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .items-table th {
      background: #e9ecef;
      color: #495057;
      font-size: 9px;
      text-transform: uppercase;
      font-weight: bold;
      padding: 7px 8px;
      border: 1px solid #ced4da;
      text-align: left;
    }
    .items-table td {
      padding: 6px 8px;
      border: 1px solid #dee2e6;
      font-size: 10px;
    }
    .items-table tr:nth-child(even) td {
      background: #fafafa;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .totals-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
    }
    .totals-table td {
      padding: 4px 8px;
      font-size: 10px;
    }
    .totals-box {
      width: 45%;
      float: right;
      border: 1px solid #dee2e6;
      background: #fdfdfd;
      padding: 8px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .khata-box {
      width: 48%;
      float: left;
      border: 1px solid #f5c2c7;
      background: #f8d7da;
      padding: 8px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .clear { clear: both; }
    .words-box {
      margin-top: 10px;
      padding: 6px 10px;
      background: #e7f1ff;
      border-left: 3px solid #0d6efd;
      font-size: 9px;
      color: #084298;
    }
    .footer-table {
      width: 100%;
      margin-top: 40px;
      border-collapse: collapse;
    }
    .footer-table td {
      text-align: center;
      width: 50%;
      vertical-align: top;
      padding-top: 10px;
    }
    .signature-line {
      border-top: 1px dashed #6c757d;
      width: 180px;
      margin: 0 auto 4px auto;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <table class="header-table">
    <tr>
      <td style="width: 55%;">
        <div class="shop-title">{{ $tenant->name }}</div>
        <div class="shop-meta">
          {{ $tenant->address ?? 'Subzi Mandi Wholesale & Retail Market' }}<br>
          {{ $tenant->city }} | Phone: {{ $tenant->phone ?? 'N/A' }}
        </div>
      </td>
      <td style="width: 45%; text-align: right;">
        <div class="invoice-title">CREDIT SALE INVOICE / بل ادھار</div>
        <div class="invoice-meta">
          <strong>Bill #:</strong> {{ $salesInvoice->invoice_no }}<br>
          <strong>Date:</strong> {{ $salesInvoice->date ? $salesInvoice->date->format('d-M-Y') : '' }}<br>
          <strong>Branch:</strong> {{ $salesInvoice->branch?->name ?? 'Main Outlet' }}
        </div>
      </td>
    </tr>
  </table>

  <!-- Customer Details -->
  <table class="info-table">
    <tr>
      <td>
        <div class="info-label">Customer / گاہک:</div>
        <div class="info-val">{{ $customer?->name ?? 'Walk-in Credit' }} @if($customer?->name_ur)({{ $customer->name_ur }})@endif</div>
        <div class="shop-meta" style="margin-top: 3px;">
          @if($customer?->phone) Phone: {{ $customer->phone }} | @endif
          @if($customer?->code) Code: {{ $customer->code }} @endif
        </div>
      </td>
      <td>
        <div class="info-label">Payment Mode / کھاتہ ادھار:</div>
        <div class="info-val" style="color: #dc3545;">CREDIT ACCOUNT (ادھار)</div>
        <div class="shop-meta" style="margin-top: 3px;">
          Status: <strong>{{ strtoupper($salesInvoice->payment_status) }}</strong>
        </div>
      </td>
    </tr>
  </table>

  <!-- Items Table -->
  <table class="items-table">
    <thead>
      <tr>
        <th style="width: 25px;" class="text-center">#</th>
        <th>Item Description / تفصیل سبزی و فروٹ</th>
        <th style="width: 55px;" class="text-center">Unit</th>
        <th style="width: 70px;" class="text-right">Qty</th>
        <th style="width: 80px;" class="text-right">Rate ({{ $tenant->currency }})</th>
        <th style="width: 95px;" class="text-right">Amount ({{ $tenant->currency }})</th>
      </tr>
    </thead>
    <tbody>
      @foreach($salesInvoice->items as $idx => $item)
        <tr>
          <td class="text-center">{{ $idx + 1 }}</td>
          <td>
            <strong>{{ $item->product?->name ?? 'Item' }}</strong>
            @if($item->product?->name_ur)
              <span style="color: #6c757d;">({{ $item->product->name_ur }})</span>
            @endif
          </td>
          <td class="text-center">{{ $item->unit?->code ?? $item->product?->stockUnit?->code ?? 'kg' }}</td>
          <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
          <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
          <td class="text-right"><strong>{{ number_format($item->subtotal, 2) }}</strong></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Khata Ledger Box (Left) & Totals (Right) -->
  <div>
    <!-- Left: Customer Khata Impact -->
    <div class="khata-box">
      <div style="font-weight: bold; color: #842029; font-size: 10px; margin-bottom: 4px;">
        CUSTOMER KHATA STATEMENT / ادھار کھاتہ:
      </div>
      <table style="width: 100%; font-size: 9px; line-height: 1.4;">
        <tr>
          <td>Current Bill Due:</td>
          <td class="text-right"><strong>{{ $tenant->currency }} {{ number_format($salesInvoice->due_amount, 2) }}</strong></td>
        </tr>
        @if($customer)
          <tr style="border-top: 1px dashed #f5c2c7;">
            <td style="color: #842029; font-weight: bold; padding-top: 3px;">TOTAL OUTSTANDING BALANCE:</td>
            <td class="text-right" style="color: #842029; font-weight: bold; font-size: 11px; padding-top: 3px;">
              {{ $tenant->currency }} {{ number_format($customer->current_balance, 2) }}
            </td>
          </tr>
        @endif
      </table>
    </div>

    <!-- Right: Bill Financial Summary -->
    <div class="totals-box">
      <table class="totals-table">
        <tr>
          <td>Items Subtotal:</td>
          <td class="text-right">{{ $tenant->currency }} {{ number_format($salesInvoice->subtotal, 2) }}</td>
        </tr>
        @if($salesInvoice->delivery_charges > 0)
          <tr>
            <td>Delivery / Carriage:</td>
            <td class="text-right">+{{ $tenant->currency }} {{ number_format($salesInvoice->delivery_charges, 2) }}</td>
          </tr>
        @endif
        @if($salesInvoice->discount_amount > 0)
          <tr>
            <td>Discount:</td>
            <td class="text-right">-{{ $tenant->currency }} {{ number_format($salesInvoice->discount_amount, 2) }}</td>
          </tr>
        @endif
        <tr style="border-top: 1px solid #ced4da; font-weight: bold; font-size: 11px;">
          <td>TOTAL BILL:</td>
          <td class="text-right" style="color: #0d6efd;">{{ $tenant->currency }} {{ number_format($salesInvoice->total_amount, 2) }}</td>
        </tr>
        @if($salesInvoice->paid_amount > 0)
          <tr style="color: #198754;">
            <td>Paid on Spot:</td>
            <td class="text-right">{{ $tenant->currency }} {{ number_format($salesInvoice->paid_amount, 2) }}</td>
          </tr>
        @endif
        <tr style="color: #dc3545; font-weight: bold;">
          <td>NET DUE (ADDED TO KHATA):</td>
          <td class="text-right">{{ $tenant->currency }} {{ number_format($salesInvoice->due_amount, 2) }}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="clear"></div>

  <!-- Amount in Words -->
  @if(!empty($amountInWords))
    <div class="words-box">
      <strong>Amount in Words:</strong> {{ $amountInWords }} Only
    </div>
  @endif

  @if($salesInvoice->notes)
    <div style="margin-top: 8px; font-size: 9px; color: #555;">
      <strong>Notes:</strong> {{ $salesInvoice->notes }}
    </div>
  @endif

  <!-- Signatures -->
  <table class="footer-table">
    <tr>
      <td>
        <div class="signature-line"></div>
        <span style="font-size: 9px; color: #6c757d;">Customer Signature / دستخط گاہک</span>
      </td>
      <td>
        <div class="signature-line"></div>
        <span style="font-size: 9px; color: #6c757d;">Authorized Signatory / دستخط دکاندار</span>
      </td>
    </tr>
  </table>

  <div style="text-align: center; margin-top: 25px; font-size: 8px; color: #888;">
    Thank you for your business! Fresh produce delivered daily. | کمپیوٹرائزڈ رسید برائے ادھار کھاتہ
  </div>

</body>
</html>
