<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected ReportingService $reportingService
    ) {}

    /**
     * Display tenant business executive dashboard.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $selectedBranchId = $request->filled('branch_id')
            ? (int) $request->branch_id
            : ($user->current_branch_id ?? null);

        $metrics = $this->reportingService->getDashboardMetrics($tenant, $selectedBranchId ?: null);

        return view('tenant.dashboard', compact('metrics', 'branches', 'selectedBranchId', 'tenant'));
    }
}
