<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\BillingCycle;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Display listing of all tenants.
     */
    public function index(Request $request): View
    {
        $query = Tenant::with(['owner', 'plan', 'branches']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $tenants = $query->latest()->paginate(10)->withQueryString();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.tenants.index', compact('tenants', 'plans'));
    }

    /**
     * Show full detail of a specific tenant.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['owner', 'plan', 'branches', 'currentSubscription', 'invoices' => fn ($q) => $q->latest()->take(10)]);
        $availablePlans = SubscriptionPlan::where('is_active', true)->get();

        // Calculate usage statistics
        $usage = [
            'branches_count' => $tenant->branches()->count(),
            'users_count' => $tenant->users()->count(),
            'records_count' => $tenant->records()->count(),
            'total_invoiced' => $tenant->invoices()->sum('amount'),
        ];

        return view('superadmin.tenants.show', compact('tenant', 'availablePlans', 'usage'));
    }

    /**
     * Show form to edit tenant details.
     */
    public function edit(Tenant $tenant): View
    {
        return view('superadmin.tenants.edit', compact('tenant'));
    }

    /**
     * Update tenant information.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'default_locale' => ['required', 'string', 'in:en,ur'],
        ]);

        $tenant->update($validated);

        return redirect()->route('superadmin.tenants.show', $tenant->id)->with('success', "Tenant '{$tenant->name}' updated successfully.");
    }

    /**
     * Toggle tenant status between Active and Suspended.
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === TenantStatus::Active) {
            $tenant->update(['status' => TenantStatus::Suspended]);
            $msg = "Tenant '{$tenant->name}' has been SUSPENDED. All operational activities are blocked.";
        } else {
            $tenant->update(['status' => TenantStatus::Active]);
            $msg = "Tenant '{$tenant->name}' has been ACTIVATED successfully.";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Manually change or assign a subscription plan.
     */
    public function changePlan(Request $request, Tenant $tenant, SubscriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string'],
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $cycle = BillingCycle::from($validated['billing_cycle']);
        $amount = (float) ($validated['amount_paid'] ?? 0);

        $service->assignPlan(
            tenant: $tenant,
            plan: $plan,
            cycle: $cycle,
            amountPaid: $amount,
            paymentMethod: $validated['payment_method'],
            notes: "Super Admin assigned plan: {$plan->name}"
        );

        return redirect()->back()->with('success', "Plan for '{$tenant->name}' changed to '{$plan->name}' successfully.");
    }

    /**
     * Manually extend subscription / trial period.
     */
    public function extendSubscription(Request $request, Tenant $tenant, SubscriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service->renewSubscription(
            tenant: $tenant,
            durationMonths: (int) $validated['months'],
            amountPaid: (float) ($validated['amount_paid'] ?? 0),
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? 'Super Admin manual subscription extension.'
        );

        return redirect()->back()->with('success', "Subscription for '{$tenant->name}' extended by {$validated['months']} month(s).");
    }
}
