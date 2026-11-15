<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinancialAccount;
use App\Models\PartyLedgerEntry;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): View
    {
        Gate::authorize('suppliers.view');

        $tenant = $this->context->getTenant();

        $query = Supplier::where('tenant_id', $tenant->id);

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
                    ->orWhere('mandi_stall_no', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        $types = [
            'commission_agent' => __('Mandi Commission Agent (Aarthi)'),
            'farmer' => __('Direct Farmer / Grower'),
            'wholesaler' => __('Wholesale Merchant'),
            'other' => __('Other Supplier'),
        ];

        // Summary metrics
        $totalPayable = Supplier::where('tenant_id', $tenant->id)->sum('current_balance');
        $activeSuppliersCount = Supplier::where('tenant_id', $tenant->id)->where('is_active', true)->count();

        return view('tenant.suppliers.index', compact('suppliers', 'types', 'tenant', 'totalPayable', 'activeSuppliersCount'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): View
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();

        $types = [
            'commission_agent' => __('Mandi Commission Agent (Aarthi)'),
            'farmer' => __('Direct Farmer / Grower'),
            'wholesaler' => __('Wholesale Merchant'),
            'other' => __('Other Supplier'),
        ];

        return view('tenant.suppliers.create', compact('tenant', 'types'));
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:commission_agent,farmer,wholesaler,other'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'mandi_stall_no' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_title' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $code = ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : Supplier::generateCode($tenant);

        if (Supplier::where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A supplier with this code already exists.')]);
        }

        $openingBalance = (float) ($validated['opening_balance'] ?? 0.00);

        return DB::transaction(function () use ($tenant, $validated, $code, $openingBalance, $request) {
            $supplier = Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'name_ur' => $validated['name_ur'] ?? null,
                'code' => $code,
                'type' => $validated['type'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'mandi_stall_no' => $validated['mandi_stall_no'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'commission_rate' => $validated['commission_rate'] ?? 0.00,
                'opening_balance' => $openingBalance,
                'current_balance' => $openingBalance,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_title' => $validated['bank_account_title'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'payment_terms_days' => $validated['payment_terms_days'] ?? 7,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $validated['notes'] ?? null,
            ]);

            // If opening balance exists, record initial ledger entry
            if ($openingBalance != 0) {
                PartyLedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'party_type' => 'supplier',
                    'party_id' => $supplier->id,
                    'date' => now()->toDateString(),
                    'reference_type' => 'opening_balance',
                    'reference_no' => 'OB-'.$supplier->code,
                    'description' => __('Opening Balance Carried Forward'),
                    'debit' => $openingBalance < 0 ? abs($openingBalance) : 0.00,
                    'credit' => $openingBalance > 0 ? $openingBalance : 0.00,
                    'balance' => $openingBalance,
                    'created_by' => auth()->id(),
                ]);
            }

            AuditLog::log(
                event: 'supplier.created',
                action: "Created supplier '{$supplier->name}' [{$supplier->code}]",
                auditable: $supplier,
                newValues: $supplier->toArray()
            );

            return redirect()->route('tenant.suppliers.index')->with('success', __('Supplier profile created successfully.'));
        });
    }

    /**
     * Display the specified supplier profile and ledger entries.
     */
    public function show(Supplier $supplier): View
    {
        Gate::authorize('suppliers.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        $ledgerEntries = $supplier->ledgerEntries()->with('creator')->paginate(20);
        $accounts = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        return view('tenant.suppliers.show', compact('supplier', 'ledgerEntries', 'accounts', 'tenant'));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier): View
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        $types = [
            'commission_agent' => __('Mandi Commission Agent (Aarthi)'),
            'farmer' => __('Direct Farmer / Grower'),
            'wholesaler' => __('Wholesale Merchant'),
            'other' => __('Other Supplier'),
        ];

        return view('tenant.suppliers.edit', compact('supplier', 'types', 'tenant'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:commission_agent,farmer,wholesaler,other'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'mandi_stall_no' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_title' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $code = strtoupper(trim($validated['code']));
        if (Supplier::where('tenant_id', $tenant->id)->where('code', $code)->where('id', '!=', $supplier->id)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A supplier with this code already exists.')]);
        }

        $oldValues = $supplier->toArray();

        $supplier->update([
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => $code,
            'type' => $validated['type'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'mandi_stall_no' => $validated['mandi_stall_no'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? 0.00,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_title' => $validated['bank_account_title'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'payment_terms_days' => $validated['payment_terms_days'] ?? 7,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log(
            event: 'supplier.updated',
            action: "Updated supplier '{$supplier->name}'",
            auditable: $supplier,
            oldValues: $oldValues,
            newValues: $supplier->toArray()
        );

        return redirect()->route('tenant.suppliers.index')->with('success', __('Supplier updated successfully.'));
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        if ($supplier->current_balance != 0) {
            return back()->with('error', __('Cannot archive supplier with outstanding unsettled balance.'));
        }

        $name = $supplier->name;
        $supplier->delete();

        AuditLog::log(
            event: 'supplier.deleted',
            action: "Soft deleted supplier '{$name}'"
        );

        return redirect()->route('tenant.suppliers.index')->with('success', __('Supplier archived successfully.'));
    }

    /**
     * Record a manual payment to a supplier.
     */
    public function recordPayment(Request $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('suppliers.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'account_id' => ['required', 'exists:financial_accounts,id'],
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = FinancialAccount::where('tenant_id', $tenant->id)->findOrFail($validated['account_id']);

        return DB::transaction(function () use ($tenant, $supplier, $account, $validated) {
            $amount = (float) $validated['amount'];

            // 1. Deduct from financial account
            $account->decrement('current_balance', $amount);

            // 2. Reduce supplier payable balance (debit supplier)
            $newBalance = $supplier->current_balance - $amount;
            $supplier->update(['current_balance' => $newBalance]);

            // 3. Create ledger entry
            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'supplier',
                'party_id' => $supplier->id,
                'date' => $validated['date'],
                'reference_type' => 'payment',
                'reference_no' => $validated['reference_no'] ?? 'PMT-'.now()->format('ymdHis'),
                'description' => $validated['description'] ?: __('Payment via ').$account->name,
                'debit' => $amount,
                'credit' => 0.00,
                'balance' => $newBalance,
                'created_by' => auth()->id(),
            ]);

            AuditLog::log(
                event: 'supplier.payment',
                action: "Recorded payment of {$tenant->currency} {$amount} to {$supplier->name} from {$account->name}",
                auditable: $supplier
            );

            return redirect()->route('tenant.suppliers.show', $supplier)->with('success', __('Payment recorded and ledger updated successfully.'));
        });
    }
}
