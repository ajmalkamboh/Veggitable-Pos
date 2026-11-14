<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::withCount('tenants')
            ->orderBy('sort_order')
            ->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    /**
     * Show form for creating a new plan.
     */
    public function create(): View
    {
        $availableFeatures = SubscriptionPlan::availableFeatures();

        return view('superadmin.plans.create', compact('availableFeatures'));
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'grace_days' => ['required', 'integer', 'min:0'],
            'max_branches' => ['required', 'integer'],
            'max_users' => ['required', 'integer'],
            'max_products' => ['required', 'integer'],
            'features' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['slug'] = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_popular'] = $request->boolean('is_popular', false);
        $validated['features'] = $request->input('features', []);

        $plan = SubscriptionPlan::create($validated);

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Show form for editing a plan.
     */
    public function edit(SubscriptionPlan $plan): View
    {
        $availableFeatures = SubscriptionPlan::availableFeatures();

        return view('superadmin.plans.edit', compact('plan', 'availableFeatures'));
    }

    /**
     * Update the plan in storage.
     */
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:subscription_plans,slug,'.$plan->id],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'grace_days' => ['required', 'integer', 'min:0'],
            'max_branches' => ['required', 'integer'],
            'max_users' => ['required', 'integer'],
            'max_products' => ['required', 'integer'],
            'features' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['features'] = $request->input('features', []);

        $plan->update($validated);

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Delete / deactivate a plan.
     */
    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->tenants()->count() > 0) {
            $plan->update(['is_active' => false]);

            return redirect()->back()->with('warning', "Plan '{$plan->name}' has active subscribers and was deactivated instead of deleted.");
        }

        $plan->delete();

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' deleted successfully.");
    }
}
