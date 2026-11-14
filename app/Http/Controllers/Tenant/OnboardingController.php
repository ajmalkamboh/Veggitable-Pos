<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Services\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected TenantOnboardingService $onboardingService
    ) {}

    /**
     * Show Onboarding Wizard Overview and Checklist.
     */
    public function index(): View|RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $progress = $tenant->onboardingProgressPercentage();
        $headquarters = $tenant->headquarters ?? $tenant->branches()->first();
        $accountsCount = $tenant->accounts()->count();
        $unitsCount = $tenant->units()->count();
        $categoriesCount = $tenant->categories()->count();

        return view('tenant.onboarding.index', compact(
            'tenant',
            'progress',
            'headquarters',
            'accountsCount',
            'unitsCount',
            'categoriesCount'
        ));
    }

    /**
     * Step 1: Business Profile, Logo, Currency, Fiscal Year.
     */
    public function step1(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        return view('tenant.onboarding.step1', compact('tenant'));
    }

    /**
     * Save Step 1 Profile Data.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'default_locale' => ['required', 'in:en,ur'],
            'financial_year_start' => ['required', 'string', 'max:10'],
            'business_opening_date' => ['nullable', 'date'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = $path;
        }

        unset($validated['logo']);
        $validated['onboarding_step'] = max($tenant->onboarding_step, 2);

        $tenant->update($validated);
        AuditLog::record('onboarding.step1_completed', ['business' => $tenant->name], auth()->user(), $tenant->id);

        return redirect()->route('tenant.onboarding.step2')->with('success', 'Business profile saved successfully.');
    }

    /**
     * Step 2: Branch & Outlet Setup.
     */
    public function step2(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $headquarters = $tenant->headquarters ?? $tenant->branches()->first();
        $branches = $tenant->branches;

        return view('tenant.onboarding.step2', compact('tenant', 'headquarters', 'branches'));
    }

    /**
     * Save Step 2 Branch Data.
     */
    public function saveBranch(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $validated = $request->validate([
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_code' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $hq = $tenant->headquarters ?? $tenant->branches()->first();

        if ($hq) {
            $hq->update([
                'name' => $validated['branch_name'],
                'code' => $validated['branch_code'],
                'city' => $validated['city'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'is_headquarters' => true,
                'is_active' => true,
            ]);
        } else {
            Branch::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['branch_name'],
                'code' => $validated['branch_code'],
                'city' => $validated['city'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'is_headquarters' => true,
                'is_active' => true,
            ]);
        }

        $tenant->update(['onboarding_step' => max($tenant->onboarding_step, 3)]);
        AuditLog::record('onboarding.step2_completed', ['branch' => $validated['branch_name']], auth()->user(), $tenant->id);

        return redirect()->route('tenant.onboarding.step3')->with('success', 'Branch details saved successfully.');
    }

    /**
     * Step 3: Cash & Bank Accounts Setup.
     */
    public function step3(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $cashAccount = $tenant->accounts()->where('type', 'cash')->first();
        $bankAccount = $tenant->accounts()->where('type', 'bank')->first();

        return view('tenant.onboarding.step3', compact('tenant', 'cashAccount', 'bankAccount'));
    }

    /**
     * Save Step 3 Accounts Data.
     */
    public function saveAccounts(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $validated = $request->validate([
            'cash_drawer_name' => ['required', 'string', 'max:100'],
            'cash_opening_balance' => ['required', 'numeric', 'min:0'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bank_opening_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = $tenant->headquarters?->id ?? $tenant->branches()->first()?->id;

        // 1. Cash Counter Drawer
        FinancialAccount::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'type' => 'cash'],
            [
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'name' => $validated['cash_drawer_name'],
                'opening_balance' => (float) $validated['cash_opening_balance'],
                'current_balance' => (float) $validated['cash_opening_balance'],
                'is_active' => true,
            ]
        );

        // 2. Bank Account (if provided)
        if (! empty($validated['bank_name']) || ! empty($validated['bank_account_name'])) {
            FinancialAccount::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'type' => 'bank'],
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branchId,
                    'name' => $validated['bank_account_name'] ?? 'Primary Business Account',
                    'bank_name' => $validated['bank_name'],
                    'account_number' => $validated['account_number'],
                    'opening_balance' => (float) ($validated['bank_opening_balance'] ?? 0),
                    'current_balance' => (float) ($validated['bank_opening_balance'] ?? 0),
                    'is_active' => true,
                ]
            );
        }

        $tenant->update(['onboarding_step' => max($tenant->onboarding_step, 4)]);
        AuditLog::record('onboarding.step3_completed', ['cash_opening' => $validated['cash_opening_balance']], auth()->user(), $tenant->id);

        return redirect()->route('tenant.onboarding.step4')->with('success', 'Financial accounts configured.');
    }

    /**
     * Step 4: Units, Expense & Product Categories Setup.
     */
    public function step4(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        // Pre-populate if not yet seeded
        if ($tenant->units()->count() === 0) {
            $this->onboardingService->seedDefaultUnits($tenant);
        }
        if ($tenant->categories()->count() === 0) {
            $this->onboardingService->seedDefaultCategories($tenant);
        }

        $units = $tenant->units;
        $productCategories = $tenant->categories()->where('type', 'product')->get();
        $expenseCategories = $tenant->categories()->where('type', 'expense')->get();

        return view('tenant.onboarding.step4', compact('tenant', 'units', 'productCategories', 'expenseCategories'));
    }

    /**
     * Save Step 4 Defaults & Finish Onboarding.
     */
    public function finish(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $this->onboardingService->completeOnboarding($tenant);

        return redirect()->route('dashboard')->with('success', 'Congratulations! Your business onboarding and configuration is complete. You can now begin vegetable shop operations.');
    }
}
