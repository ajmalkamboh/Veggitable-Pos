<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <title>{{ __('Barcode Sticker') }} - {{ $product->name }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&family=Libre+Barcode+128&family=Libre+Barcode+EAN13+Text&display=swap">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: #f8fafc;
      padding: 20px;
    }
    .print-controls {
      max-width: 600px;
      margin: 0 auto 20px auto;
      text-align: center;
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .btn {
      display: inline-block;
      padding: 8px 20px;
      font-size: 14px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      border: 1px solid transparent;
    }
    .btn-primary {
      background: #16a34a;
      color: #fff;
    }
    .btn-secondary {
      background: #f1f5f9;
      color: #475569;
      border-color: #cbd5e1;
    }
    .sticker-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      max-width: 800px;
      margin: 0 auto;
    }
    .sticker {
      width: 50mm;
      height: 35mm;
      padding: 2mm 3mm;
      background: #fff;
      border: 1px dashed #cbd5e1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      text-align: center;
      overflow: hidden;
      page-break-inside: avoid;
    }
    .shop-title {
      font-size: 8pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #334155;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    .product-name {
      font-size: 8.5pt;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.1;
      margin-top: 1mm;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    .urdu-name {
      font-family: 'Noto Nastaliq Urdu', serif;
      font-size: 7.5pt;
      color: #475569;
      line-height: 1.2;
    }
    .barcode-container {
      margin: 1mm 0;
      line-height: 1;
    }
    .barcode-svg {
      font-family: 'Libre Barcode 128', monospace;
      font-size: 26pt;
      line-height: 0.9;
    }
    .barcode-number {
      font-family: monospace;
      font-size: 7pt;
      letter-spacing: 1px;
      color: #334155;
    }
    .price-tag {
      font-size: 9pt;
      font-weight: 800;
      color: #0f172a;
      border-top: 0.5px solid #e2e8f0;
      width: 100%;
      padding-top: 0.5mm;
    }
    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      .print-controls {
        display: none !important;
      }
      .sticker {
        border: none;
      }
    }
  </style>
</head>
<body>

  <div class="print-controls">
    <button onclick="window.print()" class="btn btn-primary">🖨️ {{ __('Print Stickers Sheet') }}</button>
    <a href="{{ route('tenant.products.show', $product) }}" class="btn btn-secondary">{{ __('Close') }}</a>
    <p style="font-size: 12px; color: #64748b; margin-top: 8px;">
      {{ __('Standard 50mm x 35mm retail shelf & packaging thermal sticker') }}
    </p>
  </div>

  <div class="sticker-grid">
    @for($i = 0; $i < 8; $i++)
      <div class="sticker">
        <div class="shop-title">{{ $tenant->name }}</div>
        <div class="product-name">{{ $product->name }}</div>
        @if($product->name_ur)
          <div class="urdu-name">{{ $product->name_ur }}</div>
        @endif
        <div class="barcode-container">
          <div class="barcode-svg">*{{ $product->barcode }}*</div>
          <div class="barcode-number">{{ $product->barcode }}</div>
        </div>
        <div class="price-tag">
          {{ $tenant->currency }} {{ number_format($product->selling_price, 2) }} / {{ $product->sellingUnit?->code ?? 'kg' }}
        </div>
      </div>
    @endfor
  </div>

</body>
</html>

