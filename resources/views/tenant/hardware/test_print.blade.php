<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Hardware Diagnostic Slip') }} - {{ $tenant->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #f4f6f8; color: #000; padding: 20px; font-size: 12px; }
        .urdu-font { font-family: 'Noto Nastaliq Urdu', serif; }
        .thermal-slip { width: 80mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 12px 14px; border: 1px dashed #000; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        @media print {
            body { background: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .thermal-slip { width: 100%; border: none; padding: 0; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin-bottom: 15px;" class="no-print">
        <button onclick="window.print()" style="background: #2e7d32; color: #fff; border: none; padding: 6px 15px; border-radius: 4px; font-weight: bold; cursor: pointer;">
            {{ __('Print Diagnostic Slip') }}
        </button>
        <button onclick="window.close()" style="background: #757575; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-left: 8px;">
            {{ __('Close') }}
        </button>
    </div>

    <div class="thermal-slip">
        <div class="header">
            <h4 style="margin: 0; text-transform: uppercase;">{{ $tenant->name }}</h4>
            <div class="urdu-font" style="font-size: 13px; font-weight: bold;">{{ __('ہارڈ ویئر ٹیسٹ پرنٹ پرچی') }}</div>
            <div style="font-size: 11px;">{{ __('Thermal Printer Diagnostic Slip') }}</div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 11px;">
            <span>{{ __('Time:') }} {{ now()->format('d/m/Y h:i A') }}</span>
            <span>{{ __('Roll: 80mm') }}</span>
        </div>

        <div class="divider"></div>

        <div><strong>{{ __('Printer Mode:') }}</strong> ESC/POS Emulation</div>
        <div><strong>{{ __('Character Set:') }}</strong> UTF-8 Unicode</div>
        <div><strong>{{ __('Bilingual Nastaliq:') }}</strong> Supported</div>

        <div class="divider"></div>

        <div style="text-align: center; font-weight: bold; margin: 4px 0;">
            *** {{ __('ENGLISH ALPHABETS') }} ***<br>
            ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
            0123456789 !@#$%^&*()
        </div>

        <div class="divider"></div>

        <div style="text-align: center; margin: 4px 0;" class="urdu-font">
            *** {{ __('اردو حروفِ تہجی و نمبرز') }} ***<br>
            تازہ ٹماٹر، آلو، پیاز، ادرک، لہسن، سیب<br>
            وزن: 2.500 کلوگرام | ریٹ: 120 روپے<br>
            کل رقم: 300 روپے (کیش کاؤنٹر وصولی)
        </div>

        <div class="divider"></div>

        <div style="font-size: 10px;">
            <div><strong>{{ __('Auto-Cutter Signal:') }}</strong> GS V 0 (\x1d\x56\x00)</div>
            <div><strong>{{ __('Cash Drawer Signal:') }}</strong> ESC p 0 25 250 (\x1b\x70\x00\x19\xfa)</div>
        </div>

        <div class="divider"></div>

        <div style="text-align: center; font-size: 10px;">
            <div class="urdu-font" style="font-size: 12px; font-weight: bold;">{{ __('پرنٹر اور ہارڈ ویئر مواصلت مکمل طور پر فعال ہے') }}</div>
            <div>{{ __('Hardware integration verified OK.') }}</div>
        </div>
    </div>
</body>
</html>

