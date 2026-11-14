<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the central Super Admin dashboard.
     */
    public function index(): View
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', TenantStatus::Active)->count();
        $suspendedTenants = Tenant::where('status', TenantStatus::Suspended)->count();
        $expiredTenants = Tenant::where('status', TenantStatus::Expired)->count();

        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $totalPlans = SubscriptionPlan::count();

        $totalRevenue = SubscriptionInvoice::where('payment_status', InvoiceStatus::Paid)->sum('amount');

        // Calculate estimated Monthly Recurring Revenue (MRR)
        $monthlyRevenue = SubscriptionInvoice::where('payment_status', InvoiceStatus::Paid)
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('amount');

        $recentTenants = Tenant::with(['owner', 'plan', 'branches'])->latest()->take(6)->get();
        $recentInvoices = SubscriptionInvoice::with(['tenant', 'plan'])->latest()->take(6)->get();

        return view('superadmin.dashboard', compact(
            'totalTenants',
            'activeTenants',
            'suspendedTenants',
            'expiredTenants',
            'totalBranches',
            'totalUsers',
            'totalPlans',
            'totalRevenue',
            'monthlyRevenue',
            'recentTenants',
            'recentInvoices'
        ));
    }
}

