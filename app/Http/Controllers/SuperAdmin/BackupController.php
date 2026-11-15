<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected SecurityAuditService $securityAuditService
    ) {}

    /**
     * Display backup archives and security audit dashboard.
     */
    public function index(): View
    {
        $backups = $this->backupService->listBackups();
        $securityMetrics = $this->securityAuditService->getSecurityMetrics();
        $isolationAudit = $this->securityAuditService->auditTenantIsolation();

        return view('superadmin.backups.index', compact('backups', 'securityMetrics', 'isolationAudit'));
    }

    /**
     * Trigger immediate database snapshot backup.
     */
    public function generate(Request $request): RedirectResponse
    {
        $compress = $request->boolean('compress', true);

        try {
            $metadata = $this->backupService->createBackup(null, $compress);

            return redirect()->route('superadmin.backups.index')
                ->with('success', "Database backup created successfully: {$metadata['filename']} ({$metadata['formatted_size']})");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    /**
     * Download backup archive securely.
     */
    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        $path = $this->backupService->getBackupPath($filename);

        if (! $path) {
            return redirect()->route('superadmin.backups.index')
                ->with('error', 'Requested backup file does not exist or has been removed.');
        }

        return response()->download($path);
    }

    /**
     * Delete backup archive.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $deleted = $this->backupService->deleteBackup($filename);

        if ($deleted) {
            return redirect()->route('superadmin.backups.index')
                ->with('success', "Backup '{$filename}' was deleted successfully.");
        }

        return redirect()->route('superadmin.backups.index')
            ->with('error', 'Unable to delete backup file.');
    }
}
