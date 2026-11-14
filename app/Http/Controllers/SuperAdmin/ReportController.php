<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display platform-wide SaaS reports.
     */
    public function index(): View
    {
        $tenantsByStatus = [
            'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'expired' => Tenant::where('status', 'expired')->count(),
        ];

        $tenantsByPlan = SubscriptionPlan::withCount('tenants')->get();

        $totalRevenue = SubscriptionInvoice::sum('amount');
        $monthlyRevenue = SubscriptionInvoice::where('paid_at', '>=', now()->subDays(30))->sum('amount');

        $systemStats = [
            'total_tenants' => Tenant::count(),
            'total_branches' => Branch::count(),
            'total_users' => User::count(),
            'total_invoices' => SubscriptionInvoice::count(),
        ];

        return view('superadmin.reports.index', compact(
            'tenantsByStatus',
            'tenantsByPlan',
            'totalRevenue',
            'monthlyRevenue',
            'systemStats'
        ));
    }
}

