@extends('layouts.admin')

@section('title', __('Hardware & Device Integration'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Hardware & Device Integration Center') }}</h4>
    <p class="text-muted small mb-0">{{ __('Configure electronic weighing scales, thermal receipt printers, cash drawers, and barcode scanners') }}</p>
  </div>
  <div class="col-md-6 text-md-end">
    <a href="{{ route('tenant.hardware.test-print') }}" target="_blank" class="btn btn-outline-success btn-sm me-2">
      <i class="bi bi-printer me-1"></i> {{ __('Print Sample Thermal Test Slip') }}
    </a>
    <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<div class="row g-4">
  <!-- Left Column: Settings Form -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-sliders me-2 text-primary"></i>{{ __('POS Hardware Configuration') }}
        </h6>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('tenant.hardware.settings') }}">
          @csrf

          <!-- Thermal Printer Settings -->
          <h6 class="fw-bold text-primary small text-uppercase mb-3">
            <i class="bi bi-printer-fill me-1"></i> {{ __('1. Thermal Receipt Printer') }}
          </h6>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Receipt Roll Paper Width') }}</label>
              <select name="printer_type" class="form-select form-select-sm">
                <option value="thermal_80mm" {{ ($hardwareSettings['printer_type'] ?? '') === 'thermal_80mm' ? 'selected' : '' }}>
                  {{ __('80mm (Standard 3.15 inch Roll - Recommended)') }}
                </option>
                <option value="thermal_58mm" {{ ($hardwareSettings['printer_type'] ?? '') === 'thermal_58mm' ? 'selected' : '' }}>
                  {{ __('58mm (Compact 2.25 inch Mobile Roll)') }}
                </option>
                <option value="a4_standard" {{ ($hardwareSettings['printer_type'] ?? '') === 'a4_standard' ? 'selected' : '' }}>
                  {{ __('A4 Sheet Laser / Inkjet Printer') }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Receipt Printing Mode') }}</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="bilingual_receipt" value="1" id="bilingualCheck"
                  {{ !empty($hardwareSettings['bilingual_receipt']) ? 'checked' : '' }}>
                <label class="form-check-label small" for="bilingualCheck">
                  {{ __('Enable Bilingual English & Nastaliq Urdu') }}
                </label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="auto_cut_paper" value="1" id="autoCutCheck"
                  {{ !empty($hardwareSettings['auto_cut_paper']) ? 'checked' : '' }}>
                <label class="form-check-label small" for="autoCutCheck">
                  {{ __('Send ESC/POS Auto-Cutter Signal (\\x1d\\x56)') }}
                </label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="open_cash_drawer" value="1" id="drawerKickCheck"
                  {{ !empty($hardwareSettings['open_cash_drawer']) ? 'checked' : '' }}>
                <label class="form-check-label small" for="drawerKickCheck">
                  {{ __('Auto Kick Cash Till Drawer (Pin 2 / \\x1b\\x70)') }}
                </label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">{{ __('Receipt Footer Greeting / Note') }}</label>
              <input type="text" name="receipt_footer_text" class="form-control form-control-sm"
                value="{{ $hardwareSettings['receipt_footer_text'] ?? 'Thank you for choosing fresh organic produce! / شکریہ' }}">
            </div>
          </div>

          <!-- Electronic Weighing Scale Settings -->
          <h6 class="fw-bold text-success small text-uppercase mb-3 pt-3 border-top">
            <i class="bi bi-speedometer2 me-1"></i> {{ __('2. Electronic Weighing Scale Integration') }}
          </h6>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Scale Connection Mode') }}</label>
              <select name="weighing_scale_mode" class="form-select form-select-sm">
                <option value="barcode_embedded" {{ ($hardwareSettings['weighing_scale_mode'] ?? '') === 'barcode_embedded' ? 'selected' : '' }}>
                  {{ __('EAN-13 Weight-Embedded Barcode Label (Supermarket Standard)') }}
                </option>
                <option value="serial_rs232" {{ ($hardwareSettings['weighing_scale_mode'] ?? '') === 'serial_rs232' ? 'selected' : '' }}>
                  {{ __('RS232 Serial Port / Web Serial API Live Weight Streaming') }}
                </option>
                <option value="manual" {{ ($hardwareSettings['weighing_scale_mode'] ?? '') === 'manual' ? 'selected' : '' }}>
                  {{ __('Manual Cashier Keyboard Weight Entry') }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('RS232 Serial Baud Rate (bps)') }}</label>
              <select name="scale_serial_baud" class="form-select form-select-sm">
                <option value="9600" {{ ($hardwareSettings['scale_serial_baud'] ?? 9600) == 9600 ? 'selected' : '' }}>9600 bps (Standard CAS / Toledo)</option>
                <option value="4800" {{ ($hardwareSettings['scale_serial_baud'] ?? 9600) == 4800 ? 'selected' : '' }}>4800 bps</option>
                <option value="2400" {{ ($hardwareSettings['scale_serial_baud'] ?? 9600) == 2400 ? 'selected' : '' }}>2400 bps</option>
                <option value="19200" {{ ($hardwareSettings['scale_serial_baud'] ?? 9600) == 19200 ? 'selected' : '' }}>19200 bps</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Weight-Embedded Barcode Prefix (EAN-13)') }}</label>
              <input type="text" name="weight_barcode_prefix" class="form-control form-control-sm font-monospace"
                value="{{ $hardwareSettings['weight_barcode_prefix'] ?? '20' }}" required>
              <small class="text-muted font-monospace" style="font-size: 11px;">e.g. 20 (Format: 20AAAAAWWWWWX)</small>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">{{ __('Price-Embedded Barcode Prefix') }}</label>
              <input type="text" name="price_barcode_prefix" class="form-control form-control-sm font-monospace"
                value="{{ $hardwareSettings['price_barcode_prefix'] ?? '21' }}" required>
              <small class="text-muted font-monospace" style="font-size: 11px;">e.g. 21 (Format: 21AAAAAPPPPPX)</small>
            </div>
          </div>

          <div class="text-end pt-3 border-top">
            <button type="submit" class="btn btn-primary btn-sm px-4">
              <i class="bi bi-save me-1"></i> {{ __('Save Hardware Preferences') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Right Column: Interactive Barcode / Scale Tester & Catalog -->
  <div class="col-lg-5">
    <!-- Live Barcode Scale Tester -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-upc-scan me-2 text-success"></i>{{ __('Interactive Scale & Barcode Tester') }}
        </h6>
      </div>
      <div class="card-body p-4">
        <p class="text-muted small mb-3">
          {{ __('Scan or paste an electronic scale barcode below to test instant PLU item code and kilogram weight decoding.') }}
        </p>

        <div class="input-group mb-3">
          <input type="text" id="testBarcode" class="form-control font-monospace" placeholder="e.g. 2000105012508" value="2000105012508">
          <button class="btn btn-success" type="button" onclick="testDecodeBarcode()">
            <i class="bi bi-search me-1"></i> {{ __('Decode') }}
          </button>
        </div>

        <div id="decodeResult" class="p-3 bg-light rounded border font-monospace small">
          <span class="text-muted">{{ __('Ready. Scan a barcode above or click Decode.') }}</span>
        </div>
      </div>
    </div>

    <!-- Supported Hardware Catalog -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0 text-dark">
          <i class="bi bi-cpu me-2 text-info"></i>{{ __('Tested & Supported Hardware Devices') }}
        </h6>
      </div>
      <div class="card-body p-3">
        <ul class="nav nav-tabs nav-tabs-bordered mb-3" role="tablist">
          <li class="nav-item">
            <button class="nav-link active small py-1" data-bs-toggle="tab" data-bs-target="#tabPrinters">{{ __('Printers') }}</button>
          </li>
          <li class="nav-item">
            <button class="nav-link small py-1" data-bs-toggle="tab" data-bs-target="#tabScales">{{ __('Weighing Scales') }}</button>
          </li>
          <li class="nav-item">
            <button class="nav-link small py-1" data-bs-toggle="tab" data-bs-target="#tabScanners">{{ __('Scanners') }}</button>
          </li>
        </ul>

        <div class="tab-content small">
          <div class="tab-pane fade show active" id="tabPrinters">
            @foreach($deviceProfiles['printers'] as $p)
              <div class="mb-2 pb-2 border-bottom">
                <strong>{{ $p['brand'] }}:</strong>
                <span class="text-muted">{{ implode(', ', $p['models']) }}</span>
                <div style="font-size: 11px;" class="text-secondary">
                  {{ implode(' / ', $p['widths']) }} | {{ implode(', ', $p['connection']) }}
                </div>
              </div>
            @endforeach
          </div>

          <div class="tab-pane fade" id="tabScales">
            @foreach($deviceProfiles['scales'] as $s)
              <div class="mb-2 pb-2 border-bottom">
                <strong>{{ $s['brand'] }}:</strong>
                <span class="text-muted">{{ implode(', ', $s['models']) }}</span>
                <div style="font-size: 11px;" class="text-secondary">{{ $s['protocol'] }}</div>
              </div>
            @endforeach
          </div>

          <div class="tab-pane fade" id="tabScanners">
            @foreach($deviceProfiles['scanners'] as $sc)
              <div class="mb-2 pb-2 border-bottom">
                <strong>{{ $sc['type'] }}:</strong>
                <span class="text-muted">{{ implode(', ', $sc['brands']) }}</span>
                <div style="font-size: 11px;" class="text-secondary">{{ implode(', ', $sc['modes']) }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function testDecodeBarcode() {
    const code = document.getElementById('testBarcode').value.trim();
    const resultBox = document.getElementById('decodeResult');
    if (!code) {
        resultBox.innerHTML = '<span class="text-danger">Please enter a barcode string.</span>';
        return;
    }

    resultBox.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> Decoding...</span>';

    fetch('{{ route("tenant.hardware.parse-barcode") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ barcode: code })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data) {
            const d = data.data;
            if (d.type === 'weight') {
                resultBox.innerHTML = `
                    <div class="text-success fw-bold mb-1"><i class="bi bi-check-circle"></i> Weight Barcode Recognized</div>
                    <div><strong>Prefix:</strong> ${d.prefix}</div>
                    <div><strong>PLU Code:</strong> ${d.plu_code}</div>
                    <div><strong>Grams:</strong> ${d.weight_grams} g</div>
                    <div class="text-primary fw-bold"><strong>Net Weight:</strong> ${d.weight_kg} kg</div>
                `;
            } else if (d.type === 'price') {
                resultBox.innerHTML = `
                    <div class="text-success fw-bold mb-1"><i class="bi bi-check-circle"></i> Price Barcode Recognized</div>
                    <div><strong>Prefix:</strong> ${d.prefix}</div>
                    <div><strong>PLU Code:</strong> ${d.plu_code}</div>
                    <div class="text-primary fw-bold"><strong>Total Price:</strong> Rs. ${d.price}</div>
                `;
            }
        } else {
            resultBox.innerHTML = `
                <div class="text-warning fw-bold"><i class="bi bi-info-circle"></i> Standard Barcode</div>
                <div class="text-muted">${data.message}</div>
                <div><strong>Raw:</strong> ${code}</div>
            `;
        }
    })
    .catch(err => {
        resultBox.innerHTML = '<span class="text-danger">Error contacting decoder endpoint.</span>';
    });
}
</script>
@endsection

