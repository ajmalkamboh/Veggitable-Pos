<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected ExpenseService $expenseService
    ) {}

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $query = Expense::where('tenant_id', $tenant->id)
            ->with(['branch', 'category', 'financialAccount', 'creator', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
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
                $q->where('expense_no', 'like', "%{$s}%")
                    ->orWhere('payee_name', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $expenses = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);

        // Metrics for summary cards
        $metricsQuery = Expense::where('tenant_id', $tenant->id)->where('status', 'approved');

        $monthExpenses = (clone $metricsQuery)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $todayExpenses = (clone $metricsQuery)
            ->whereDate('date', now()->toDateString())
            ->sum('amount');

        $pendingApprovals = Expense::where('tenant_id', $tenant->id)
            ->where('status', 'pending_approval')
            ->count();

        $totalCashBalance = FinancialAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->sum('current_balance');

        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return view('tenant.expenses.index', compact(
            'expenses',
            'monthExpenses',
            'todayExpenses',
            'pendingApprovals',
            'totalCashBalance',
            'branches',
            'categories'
        ));
    }

    /**
     * Show form to create a new expense voucher.
     */
    public function create(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();
        $activeBranchId = $request->get('branch_id', $user->current_branch_id ?? Branch::where('tenant_id', $tenant->id)->value('id'));

        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();
        $accounts = FinancialAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        return view('tenant.expenses.create', compact('branches', 'categories', 'accounts', 'activeBranchId'));
    }

    /**
     * Store a newly created expense voucher.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category_id' => 'required|exists:categories,id',
            'financial_account_id' => 'nullable|exists:financial_accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank,online,cheque',
            'payee_name' => 'nullable|string|max:255',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:approved,pending_approval',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        $expense = $this->expenseService->createExpense(
            $tenant,
            $branch,
            $user,
            $validated,
            $request->file('attachment')
        );

        return redirect()->route('tenant.expenses.show', $expense->id)
            ->with('success', __('Expense voucher :no recorded successfully.', ['no' => $expense->expense_no]));
    }

    /**
     * Show expense voucher details.
     */
    public function show(int $id): View
    {
        $tenant = $this->context->getTenant();
        $expense = Expense::where('tenant_id', $tenant->id)
            ->with(['branch', 'category', 'financialAccount', 'creator', 'approver'])
            ->findOrFail($id);

        return view('tenant.expenses.show', compact('expense'));
    }

    /**
     * Approve pending expense voucher.
     */
    public function approve(int $id): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $expense = Expense::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->expenseService->approve($expense, auth()->user());

        return redirect()->back()->with('success', __('Expense voucher :no approved.', ['no' => $expense->expense_no]));
    }

    /**
     * Reject pending expense voucher.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:255']);
        $tenant = $this->context->getTenant();
        $expense = Expense::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->expenseService->reject($expense, auth()->user(), $request->rejection_reason);

        return redirect()->back()->with('success', __('Expense voucher :no rejected.', ['no' => $expense->expense_no]));
    }

    /**
     * Cancel/delete expense voucher and refund account balance.
     */
    public function destroy(int $id): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $expense = Expense::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->expenseService->cancel($expense, auth()->user());

        return redirect()->route('tenant.expenses.index')
            ->with('success', __('Expense voucher cancelled and account balance refunded.'));
    }
}
