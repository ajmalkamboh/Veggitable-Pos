<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Services\HardwareIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HardwareController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected HardwareIntegrationService $hardwareService
    ) {}

    /**
     * Hardware & Device Integration Center.
     */
    public function index(): View
    {
        $tenant = $this->context->getTenant();
        $hardwareSettings = $tenant->settings['hardware'] ?? [
            'printer_type' => 'thermal_80mm',
            'auto_cut_paper' => true,
            'open_cash_drawer' => true,
            'weighing_scale_mode' => 'barcode_embedded',
            'weight_barcode_prefix' => '20',
            'price_barcode_prefix' => '21',
            'scale_serial_baud' => 9600,
            'bilingual_receipt' => true,
        ];

        $deviceProfiles = $this->hardwareService->getDeviceProfiles();
        $escPosCommands = $this->hardwareService->getEscPosCommands();

        return view('tenant.hardware.index', compact('tenant', 'hardwareSettings', 'deviceProfiles', 'escPosCommands'));
    }

    /**
     * Update hardware and printing preferences.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'printer_type' => ['required', 'string', 'in:thermal_80mm,thermal_58mm,a4_standard'],
            'auto_cut_paper' => ['nullable', 'boolean'],
            'open_cash_drawer' => ['nullable', 'boolean'],
            'weighing_scale_mode' => ['required', 'string', 'in:barcode_embedded,serial_rs232,manual'],
            'weight_barcode_prefix' => ['required', 'string', 'max:5'],
            'price_barcode_prefix' => ['required', 'string', 'max:5'],
            'scale_serial_baud' => ['nullable', 'integer'],
            'bilingual_receipt' => ['nullable', 'boolean'],
            'receipt_footer_text' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['hardware'] = [
            'printer_type' => $validated['printer_type'],
            'auto_cut_paper' => (bool) ($validated['auto_cut_paper'] ?? false),
            'open_cash_drawer' => (bool) ($validated['open_cash_drawer'] ?? false),
            'weighing_scale_mode' => $validated['weighing_scale_mode'],
            'weight_barcode_prefix' => $validated['weight_barcode_prefix'],
            'price_barcode_prefix' => $validated['price_barcode_prefix'],
            'scale_serial_baud' => (int) ($validated['scale_serial_baud'] ?? 9600),
            'bilingual_receipt' => (bool) ($validated['bilingual_receipt'] ?? false),
            'receipt_footer_text' => $validated['receipt_footer_text'] ?? 'Thank you for choosing fresh organic produce! / شکریہ',
        ];

        $tenant->settings = $settings;
        $tenant->save();

        return redirect()->route('tenant.hardware.index')
            ->with('success', __('Hardware and device settings updated successfully!'));
    }

    /**
     * API to decode and test scale barcodes live.
     */
    public function parseBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => ['required', 'string'],
        ]);

        $barcode = trim($request->barcode);
        $tenant = $this->context->getTenant();
        $hw = $tenant->settings['hardware'] ?? [];
        $wPrefix = $hw['weight_barcode_prefix'] ?? '20';
        $pPrefix = $hw['price_barcode_prefix'] ?? '21';

        $weightResult = $this->hardwareService->parseWeightBarcode($barcode, $wPrefix);
        if ($weightResult) {
            return response()->json([
                'success' => true,
                'data' => $weightResult,
                'message' => __('Weight-embedded produce barcode decoded successfully.'),
            ]);
        }

        $priceResult = $this->hardwareService->parsePriceBarcode($barcode, $pPrefix);
        if ($priceResult) {
            return response()->json([
                'success' => true,
                'data' => $priceResult,
                'message' => __('Price-embedded barcode decoded successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Standard barcode or non-scale format detected.'),
            'raw_barcode' => $barcode,
        ]);
    }

    /**
     * Printable thermal test voucher.
     */
    public function testPrint(): View
    {
        $tenant = $this->context->getTenant();
        $commands = $this->hardwareService->getEscPosCommands();

        return view('tenant.hardware.test_print', compact('tenant', 'commands'));
    }
}
