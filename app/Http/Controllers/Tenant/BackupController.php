<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Show tenant data export center.
     */
    public function index(): View
    {
        $tenant = $this->tenantContext->getTenant();
        $allBackups = $this->backupService->listBackups();

        // Filter only this tenant's exports
        $tenantBackups = array_filter(
            $allBackups,
            fn ($b) => ($b['type'] ?? '') === 'tenant_export' && str_contains($b['filename'], "tenant_{$tenant->slug}_")
        );

        return view('tenant.backup.index', compact('tenant', 'tenantBackups'));
    }

    /**
     * Generate an isolated data package export for the active business.
     */
    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $compress = $request->boolean('compress', false);

        try {
            $metadata = $this->backupService->exportTenantData($tenant, $compress);

            if ($request->boolean('download', true)) {
                return response()->download($metadata['path'], $metadata['filename']);
            }

            return redirect()->route('tenant.backup.index')
                ->with('success', "Tenant business data package generated successfully: {$metadata['filename']}");
        } catch (\Throwable $e) {
            return redirect()->route('tenant.backup.index')
                ->with('error', 'Data export failed: '.$e->getMessage());
        }
    }
}
