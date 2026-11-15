<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\PartyLedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        Gate::authorize('customers.view');

        $tenant = $this->context->getTenant();

        $query = Customer::where('tenant_id', $tenant->id);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->searchBilingual($search)
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        $types = [
            'retail' => __('Counter Walk-in Retail'),
            'hotel_restaurant' => __('Hotel, Restaurant & Cafe'),
            'wholesale_buyer' => __('Wholesale / Semi-Wholesale Buyer'),
            'canteen_caterer' => __('Canteen & Catering Service'),
        ];

        // Summary metrics
        $totalReceivable = Customer::where('tenant_id', $tenant->id)->sum('current_balance');
        $activeCustomersCount = Customer::where('tenant_id', $tenant->id)->where('is_active', true)->count();

        return view('tenant.customers.index', compact('customers', 'types', 'tenant', 'totalReceivable', 'activeCustomersCount'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();

        $types = [
            'retail' => __('Counter Walk-in Retail'),
            'hotel_restaurant' => __('Hotel, Restaurant & Cafe'),
            'wholesale_buyer' => __('Wholesale / Semi-Wholesale Buyer'),
            'canteen_caterer' => __('Canteen & Catering Service'),
        ];

        return view('tenant.customers.create', compact('tenant', 'types'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:retail,hotel_restaurant,wholesale_buyer,canteen_caterer'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $code = ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : Customer::generateCode($tenant);

        if (Customer::where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A customer with this code already exists.')]);
        }

        $openingBalance = (float) ($validated['opening_balance'] ?? 0.00);

        return DB::transaction(function () use ($tenant, $validated, $code, $openingBalance, $request) {
            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'name_ur' => $validated['name_ur'] ?? null,
                'code' => $code,
                'type' => $validated['type'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'tax_id' => $validated['tax_id'] ?? null,
                'credit_limit' => $validated['credit_limit'] ?? 0.00,
                'credit_days' => $validated['credit_days'] ?? 0,
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $validated['notes'] ?? null,
            ]);

            // If opening balance exists, record initial ledger entry
            if ($openingBalance != 0) {
                PartyLedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'party_type' => 'customer',
                    'party_id' => $customer->id,
                    'date' => now()->toDateString(),
                    'reference_type' => 'opening_balance',
                    'reference_no' => 'OB-'.$customer->code,
                    'description' => __('Opening Balance Carried Forward'),
                    'debit' => $openingBalance > 0 ? $openingBalance : 0.00,
                    'credit' => $openingBalance < 0 ? abs($openingBalance) : 0.00,
                    'balance' => $openingBalance,
                    'created_by' => auth()->id(),
                ]);
            }

            AuditLog::log(
                event: 'customer.created',
                action: "Created customer '{$customer->name}' [{$customer->code}]",
                auditable: $customer,
                newValues: $customer->toArray()
            );

            return redirect()->route('tenant.customers.index')->with('success', __('Customer account created successfully.'));
        });
    }

    /**
     * Display the specified customer profile and ledger history.
     */
    public function show(Customer $customer): View
    {
        Gate::authorize('customers.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        $ledgerEntries = $customer->ledgerEntries()->with('creator')->paginate(20);
        $accounts = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        return view('tenant.customers.show', compact('customer', 'ledgerEntries', 'accounts', 'tenant'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        $types = [
            'retail' => __('Counter Walk-in Retail'),
            'hotel_restaurant' => __('Hotel, Restaurant & Cafe'),
            'wholesale_buyer' => __('Wholesale / Semi-Wholesale Buyer'),
            'canteen_caterer' => __('Canteen & Catering Service'),
        ];

        return view('tenant.customers.edit', compact('customer', 'types', 'tenant'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:retail,hotel_restaurant,wholesale_buyer,canteen_caterer'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $code = strtoupper(trim($validated['code']));
        if (Customer::where('tenant_id', $tenant->id)->where('code', $code)->where('id', '!=', $customer->id)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A customer with this code already exists.')]);
        }

        $oldValues = $customer->toArray();

        $customer->update([
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => $code,
            'type' => $validated['type'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'credit_limit' => $validated['credit_limit'] ?? 0.00,
            'credit_days' => $validated['credit_days'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log(
            event: 'customer.updated',
            action: "Updated customer '{$customer->name}'",
            auditable: $customer,
            oldValues: $oldValues,
            newValues: $customer->toArray()
        );

        return redirect()->route('tenant.customers.index')->with('success', __('Customer updated successfully.'));
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        if ($customer->current_balance != 0) {
            return back()->with('error', __('Cannot archive customer account with outstanding balance.'));
        }

        $name = $customer->name;
        $customer->delete();

        AuditLog::log(
            event: 'customer.deleted',
            action: "Soft deleted customer '{$name}'"
        );

        return redirect()->route('tenant.customers.index')->with('success', __('Customer archived successfully.'));
    }

    /**
     * Record a customer payment receipt.
     */
    public function recordReceipt(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'account_id' => ['required', 'exists:financial_accounts,id'],
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = FinancialAccount::where('tenant_id', $tenant->id)->findOrFail($validated['account_id']);

        return DB::transaction(function () use ($tenant, $customer, $account, $validated) {
            $amount = (float) $validated['amount'];

            // 1. Add funds to cashier drawer or bank account
            $account->increment('current_balance', $amount);

            // 2. Reduce customer receivable balance (credit customer)
            $newBalance = $customer->current_balance - $amount;
            $customer->update(['current_balance' => $newBalance]);

            // 3. Create ledger entry
            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'customer',
                'party_id' => $customer->id,
                'date' => $validated['date'],
                'reference_type' => 'receipt',
                'reference_no' => $validated['reference_no'] ?? 'RCT-'.now()->format('ymdHis'),
                'description' => $validated['description'] ?: __('Payment received into ').$account->name,
                'debit' => 0.00,
                'credit' => $amount,
                'balance' => $newBalance,
                'created_by' => auth()->id(),
            ]);

            AuditLog::log(
                event: 'customer.receipt',
                action: "Recorded payment receipt of {$tenant->currency} {$amount} from {$customer->name} deposited into {$account->name}",
                auditable: $customer
            );

            return redirect()->route('tenant.customers.show', $customer)->with('success', __('Payment receipt logged and balance updated successfully.'));
        });
    }
}
