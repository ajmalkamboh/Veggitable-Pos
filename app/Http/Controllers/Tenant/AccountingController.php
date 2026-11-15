<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected AccountingService $accountingService
    ) {}

    /**
     * Display the Chart of Accounts.
     */
    public function coaIndex(): View
    {
        $tenant = $this->context->getTenant();
        $this->accountingService->seedDefaultChartOfAccounts($tenant);

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->orderBy('account_code')
            ->get()
            ->groupBy('type');

        return view('tenant.accounting.coa.index', compact('accounts'));
    }

    /**
     * Create a new custom Chart of Account.
     */
    public function coaCreate(): View
    {
        return view('tenant.accounting.coa.create');
    }

    /**
     * Store a new custom account.
     */
    public function coaStore(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'account_code' => 'required|string|max:50|unique:chart_of_accounts,account_code,NULL,id,tenant_id,'.$tenant->id,
            'name' => 'required|string|max:255',
            'name_ur' => 'nullable|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'required|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string|max:500',
        ]);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0);

        ChartOfAccount::create([
            'tenant_id' => $tenant->id,
            'account_code' => $validated['account_code'],
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'type' => $validated['type'],
            'subtype' => $validated['subtype'],
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'is_active' => true,
        ]);

        return redirect()->route('tenant.accounting.coa.index')
            ->with('success', __('Account :name created successfully.', ['name' => $validated['name']]));
    }

    /**
     * Display list of Journal Entries / Vouchers.
     */
    public function journalIndex(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $query = JournalEntry::where('tenant_id', $tenant->id)
            ->with(['branch', 'creator', 'lines.account']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('entry_no', 'like', "%{$s}%")
                    ->orWhere('narration', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%");
            });
        }

        $entries = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);

        return view('tenant.accounting.journal.index', compact('entries'));
    }

    /**
     * Form to create a double-entry Journal Voucher.
     */
    public function journalCreate(): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();
        $this->accountingService->seedDefaultChartOfAccounts($tenant);

        $branchId = $user->current_branch_id ?? Branch::where('tenant_id', $tenant->id)->value('id');
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('account_code')->get();

        return view('tenant.accounting.journal.create', compact('branches', 'accounts', 'branchId'));
    }

    /**
     * Store double-entry Journal Voucher.
     */
    public function journalStore(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'narration' => 'required|string|max:1000',
            'reference_type' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'status' => 'nullable|in:posted,draft',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        $entry = $this->accountingService->createJournalEntry($tenant, $branch, $user, $validated);

        return redirect()->route('tenant.accounting.journal.show', $entry->id)
            ->with('success', __('Journal voucher :no posted successfully.', ['no' => $entry->entry_no]));
    }

    /**
     * Show detailed Journal Voucher.
     */
    public function journalShow(int $id): View
    {
        $tenant = $this->context->getTenant();
        $entry = JournalEntry::where('tenant_id', $tenant->id)
            ->with(['branch', 'creator', 'lines.account'])
            ->findOrFail($id);

        return view('tenant.accounting.journal.show', compact('entry'));
    }

    /**
     * Reverse a posted Journal Voucher.
     */
    public function journalReverse(Request $request, int $id): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $tenant = $this->context->getTenant();
        $entry = JournalEntry::where('tenant_id', $tenant->id)->findOrFail($id);

        $reversal = $this->accountingService->reverseJournalEntry($entry, auth()->user(), $request->reason);

        return redirect()->route('tenant.accounting.journal.show', $reversal->id)
            ->with('success', __('Journal voucher :no reversed. Reversal entry :rev created.', ['no' => $entry->entry_no, 'rev' => $reversal->entry_no]));
    }

    /**
     * Display General Ledger statement for an account.
     */
    public function generalLedger(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $this->accountingService->seedDefaultChartOfAccounts($tenant);

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)->orderBy('account_code')->get();
        $selectedAccountId = $request->get('account_id', $accounts->first()?->id);

        $selectedAccount = $selectedAccountId ? ChartOfAccount::where('tenant_id', $tenant->id)->find($selectedAccountId) : null;
        $ledgerData = null;

        if ($selectedAccount) {
            $ledgerData = $this->accountingService->getGeneralLedger(
                $selectedAccount,
                $request->get('date_from'),
                $request->get('date_to')
            );
        }

        return view('tenant.accounting.reports.general_ledger', compact('accounts', 'selectedAccount', 'ledgerData'));
    }

    /**
     * Display Trial Balance.
     */
    public function trialBalance(): View
    {
        $tenant = $this->context->getTenant();
        $trialBalance = $this->accountingService->getTrialBalance($tenant);

        return view('tenant.accounting.reports.trial_balance', compact('trialBalance'));
    }

    /**
     * Display Profit and Loss Statement (Income Statement).
     */
    public function profitAndLoss(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $pnl = $this->accountingService->getProfitAndLoss(
            $tenant,
            $request->get('date_from'),
            $request->get('date_to')
        );

        return view('tenant.accounting.reports.profit_and_loss', compact('pnl'));
    }

    /**
     * Display Balance Sheet (Statement of Financial Position).
     */
    public function balanceSheet(): View
    {
        $tenant = $this->context->getTenant();
        $bs = $this->accountingService->getBalanceSheet($tenant);

        return view('tenant.accounting.reports.balance_sheet', compact('bs'));
    }
}
