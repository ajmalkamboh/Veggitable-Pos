<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected ReportingService $reportingService
    ) {}

    /**
     * Reports Hub view.
     */
    public function index(): View
    {
        return view('tenant.reports.index');
    }

    /**
     * Sales Analytics Report.
     */
    public function sales(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();

        $filters = $request->only(['branch_id', 'customer_id', 'date_from', 'date_to', 'status']);
        $reportData = $this->reportingService->getSalesReport($tenant, $filters);

        return view('tenant.reports.sales', compact('reportData', 'branches', 'customers'));
    }

    /**
     * Mandi Purchases & Procurement Report.
     */
    public function purchases(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $suppliers = Supplier::where('tenant_id', $tenant->id)->get();

        $filters = $request->only(['branch_id', 'supplier_id', 'date_from', 'date_to']);
        $reportData = $this->reportingService->getPurchaseReport($tenant, $filters);

        return view('tenant.reports.purchases', compact('reportData', 'branches', 'suppliers'));
    }

    /**
     * Daily Cash Register Z-Report summary.
     */
    public function dayEnd(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $selectedBranchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $date = $request->get('date', now()->toDateString());

        $reportData = $this->reportingService->getDayEndRegisterReport($tenant, $selectedBranchId, $date);

        return view('tenant.reports.day_end', compact('reportData', 'branches', 'selectedBranchId', 'date'));
    }
}
