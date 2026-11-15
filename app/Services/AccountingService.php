<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Seed default Chart of Accounts for a vegetable shop tenant.
     */
    public function seedDefaultChartOfAccounts(Tenant $tenant): void
    {
        $defaults = [
            // Assets
            ['account_code' => '1010', 'name' => 'Cash in Hand / Drawer Till', 'name_ur' => 'نقد رقم / دراز', 'type' => 'asset', 'subtype' => 'current_asset', 'is_system' => true],
            ['account_code' => '1020', 'name' => 'Bank Accounts', 'name_ur' => 'بینک اکاؤنٹ', 'type' => 'asset', 'subtype' => 'current_asset', 'is_system' => true],
            ['account_code' => '1030', 'name' => 'Accounts Receivable (Customers)', 'name_ur' => 'گاہکوں سے وصولیاں', 'type' => 'asset', 'subtype' => 'current_asset', 'is_system' => true],
            ['account_code' => '1040', 'name' => 'Inventory - Fresh Produce', 'name_ur' => 'اسٹاک مال سبزی و پھل', 'type' => 'asset', 'subtype' => 'current_asset', 'is_system' => true],
            ['account_code' => '1050', 'name' => 'Shop Fixtures & Scales', 'name_ur' => 'دکان کا فرنیچر و کانٹے', 'type' => 'asset', 'subtype' => 'non_current_asset', 'is_system' => false],

            // Liabilities
            ['account_code' => '2010', 'name' => 'Accounts Payable - Mandi Aarthi & Suppliers', 'name_ur' => 'منڈی آڑھتی و سپلائرز واجبات', 'type' => 'liability', 'subtype' => 'current_liability', 'is_system' => true],
            ['account_code' => '2020', 'name' => 'Accrued Expenses & Wages', 'name_ur' => 'واجب الادا اخراجات و تنخواہیں', 'type' => 'liability', 'subtype' => 'current_liability', 'is_system' => false],

            // Equity
            ['account_code' => '3010', 'name' => 'Owner Capital / Investment', 'name_ur' => 'مالک کا سرمایہ', 'type' => 'equity', 'subtype' => 'equity', 'is_system' => true],
            ['account_code' => '3020', 'name' => 'Owner Drawings', 'name_ur' => 'ذاتی استعمال / ڈرائنگ', 'type' => 'equity', 'subtype' => 'equity', 'is_system' => false],

            // Revenue
            ['account_code' => '4010', 'name' => 'Produce Sales Revenue', 'name_ur' => 'سبزی و فروٹ کی فروخت آمدنی', 'type' => 'revenue', 'subtype' => 'operating_revenue', 'is_system' => true],
            ['account_code' => '4020', 'name' => 'Other Miscellaneous Income', 'name_ur' => 'دیگر متفرق آمدنی', 'type' => 'revenue', 'subtype' => 'operating_revenue', 'is_system' => false],

            // Expenses / COGS
            ['account_code' => '5010', 'name' => 'Cost of Goods Sold (Procurement)', 'name_ur' => 'خریداری مال و منڈی لاگت', 'type' => 'expense', 'subtype' => 'direct_expense', 'is_system' => true],
            ['account_code' => '5020', 'name' => 'Mandi Carriage & Freight', 'name_ur' => 'منڈی کرایہ و باربرداری', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => false],
            ['account_code' => '5030', 'name' => 'Staff Salaries & Daily Wages', 'name_ur' => 'ملازمین کی تنخواہیں و دیہاڑی', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => false],
            ['account_code' => '5040', 'name' => 'Shop Rent & Utilities', 'name_ur' => 'دکان کا کرایہ و بجلی بل', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => false],
            ['account_code' => '5050', 'name' => 'Produce Spoilage & Wastage Loss', 'name_ur' => 'خراب مال و سوکھنے کا نقصان', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => true],
            ['account_code' => '5060', 'name' => 'Daily Tea & Refreshments', 'name_ur' => 'روزانہ چائے کھانا', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => false],
            ['account_code' => '5070', 'name' => 'Miscellaneous General Expenses', 'name_ur' => 'متفرق اخراجات', 'type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => false],
        ];

        foreach ($defaults as $acc) {
            ChartOfAccount::firstOrCreate(
                ['tenant_id' => $tenant->id, 'account_code' => $acc['account_code']],
                array_merge($acc, ['tenant_id' => $tenant->id])
            );
        }
    }

    /**
     * Create and post a double-entry Journal Voucher (JV).
     */
    public function createJournalEntry(Tenant $tenant, Branch $branch, User $user, array $data): JournalEntry
    {
        return DB::transaction(function () use ($tenant, $branch, $user, $data) {
            $lines = $data['lines'] ?? [];
            if (count($lines) < 2) {
                throw new \DomainException(__('A journal entry must contain at least two lines.'));
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }

            $totalDebit = round($totalDebit, 2);
            $totalCredit = round($totalCredit, 2);

            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new \DomainException(__(
                    'Journal entry does not balance! Total Debit (:d) must equal Total Credit (:c).',
                    ['d' => number_format($totalDebit, 2), 'c' => number_format($totalCredit, 2)]
                ));
            }

            $prefix = 'JV-'.date('ym');
            $lastEntry = JournalEntry::where('tenant_id', $tenant->id)
                ->where('entry_no', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastEntry && preg_match('/-(\d+)$/', $lastEntry->entry_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $entryNo = sprintf('%s-%05d', $prefix, $nextNumber);

            $status = $data['status'] ?? 'posted';
            $isPosted = ($status === 'posted');

            $entry = JournalEntry::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'entry_no' => $entryNo,
                'date' => $data['date'] ?? now()->toDateString(),
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'narration' => $data['narration'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => $status,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $account = ChartOfAccount::where('tenant_id', $tenant->id)->findOrFail($line['account_id']);
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                JournalEntryLine::create([
                    'tenant_id' => $tenant->id,
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'description' => $line['description'] ?? $data['narration'],
                    'debit' => $debit,
                    'credit' => $credit,
                ]);

                if ($isPosted) {
                    $this->updateAccountBalance($account, $debit, $credit);
                }
            }

            AuditLog::log(
                event: 'accounting.journal_posted',
                action: "Posted journal voucher '{$entry->entry_no}' for Rs. {$totalDebit}",
                auditable: $entry
            );

            return $entry->load(['lines.account', 'branch', 'creator']);
        });
    }

    /**
     * Reverse a posted journal entry by creating an offsetting entry.
     */
    public function reverseJournalEntry(JournalEntry $entry, User $user, string $reason): JournalEntry
    {
        return DB::transaction(function () use ($entry, $user, $reason) {
            if ($entry->status !== 'posted') {
                throw new \DomainException(__('Only posted journal entries can be reversed.'));
            }

            $entry->load('lines.account', 'tenant', 'branch');
            $tenant = $entry->tenant;
            $branch = $entry->branch;

            // Flip debits and credits
            $reversedLines = [];
            foreach ($entry->lines as $line) {
                $reversedLines[] = [
                    'account_id' => $line->account_id,
                    'description' => __('Reversal: ').$line->description,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                ];
            }

            $reversalEntry = $this->createJournalEntry($tenant, $branch, $user, [
                'date' => now()->toDateString(),
                'reference_type' => 'manual',
                'reference_no' => 'REV-'.$entry->entry_no,
                'narration' => __('Reversal of :no - Reason: :r', ['no' => $entry->entry_no, 'r' => $reason]),
                'lines' => $reversedLines,
                'status' => 'posted',
            ]);

            $entry->update(['status' => 'reversed']);

            return $reversalEntry;
        });
    }

    /**
     * Update running balance on Chart of Account according to its normal balance side.
     */
    protected function updateAccountBalance(ChartOfAccount $account, float $debit, float $credit): void
    {
        $current = (float) $account->current_balance;

        if ($account->isDebitNormal()) {
            // Assets & Expenses increase on Debit
            $newBalance = $current + ($debit - $credit);
        } else {
            // Liabilities, Equity & Revenue increase on Credit
            $newBalance = $current + ($credit - $debit);
        }

        $account->update(['current_balance' => round($newBalance, 2)]);
    }

    /**
     * Calculate Trial Balance across all accounts.
     */
    public function getTrialBalance(Tenant $tenant): array
    {
        $this->seedDefaultChartOfAccounts($tenant);

        $accounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->withSum('journalLines as total_debit', 'debit')
            ->withSum('journalLines as total_credit', 'credit')
            ->orderBy('account_code')
            ->get();

        $rows = [];
        $sumDebits = 0.0;
        $sumCredits = 0.0;

        foreach ($accounts as $acc) {
            $totalDebit = (float) ($acc->total_debit ?? 0);
            $totalCredit = (float) ($acc->total_credit ?? 0);

            // Factor in opening balance
            if ($acc->isDebitNormal()) {
                $totalDebit += (float) $acc->opening_balance;
            } else {
                $totalCredit += (float) $acc->opening_balance;
            }

            $debitBalance = 0.0;
            $creditBalance = 0.0;

            if ($totalDebit >= $totalCredit) {
                $debitBalance = round($totalDebit - $totalCredit, 2);
            } else {
                $creditBalance = round($totalCredit - $totalDebit, 2);
            }

            if ($debitBalance > 0 || $creditBalance > 0) {
                $rows[] = [
                    'account' => $acc,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];

                $sumDebits += $debitBalance;
                $sumCredits += $creditBalance;
            }
        }

        return [
            'rows' => $rows,
            'total_debits' => round($sumDebits, 2),
            'total_credits' => round($sumCredits, 2),
            'is_balanced' => abs($sumDebits - $sumCredits) < 0.01,
        ];
    }

    /**
     * Generate Profit & Loss statement for date period.
     */
    public function getProfitAndLoss(Tenant $tenant, ?string $fromDate = null, ?string $toDate = null): array
    {
        $this->seedDefaultChartOfAccounts($tenant);

        $revenueAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'revenue')
            ->get();

        $cogsAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->where('subtype', 'direct_expense')
            ->get();

        $operatingExpenseAccounts = ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->where('subtype', '!=', 'direct_expense')
            ->get();

        $totalRevenue = 0.0;
        $revenueRows = [];
        foreach ($revenueAccounts as $rev) {
            $bal = (float) $rev->current_balance;
            $revenueRows[] = ['account' => $rev, 'amount' => $bal];
            $totalRevenue += $bal;
        }

        $totalCogs = 0.0;
        $cogsRows = [];
        foreach ($cogsAccounts as $cog) {
            $bal = (float) $cog->current_balance;
            $cogsRows[] = ['account' => $cog, 'amount' => $bal];
            $totalCogs += $bal;
        }

        $grossProfit = round($totalRevenue - $totalCogs, 2);

        $totalOperatingExpenses = 0.0;
        $expenseRows = [];
        foreach ($operatingExpenseAccounts as $exp) {
            $bal = (float) $exp->current_balance;
            $expenseRows[] = ['account' => $exp, 'amount' => $bal];
            $totalOperatingExpenses += $bal;
        }

        $netProfit = round($grossProfit - $totalOperatingExpenses, 2);

        return [
            'revenue_rows' => $revenueRows,
            'total_revenue' => $totalRevenue,
            'cogs_rows' => $cogsRows,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expense_rows' => $expenseRows,
            'total_operating_expenses' => $totalOperatingExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate Balance Sheet (Statement of Financial Position).
     */
    public function getBalanceSheet(Tenant $tenant): array
    {
        $this->seedDefaultChartOfAccounts($tenant);

        $assets = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'asset')->get();
        $liabilities = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'liability')->get();
        $equity = ChartOfAccount::where('tenant_id', $tenant->id)->where('type', 'equity')->get();

        $pnl = $this->getProfitAndLoss($tenant);
        $netProfit = $pnl['net_profit'];

        $totalAssets = $assets->sum('current_balance');
        $totalLiabilities = $liabilities->sum('current_balance');
        $totalEquity = $equity->sum('current_balance') + $netProfit;

        return [
            'assets' => $assets,
            'total_assets' => round($totalAssets, 2),
            'liabilities' => $liabilities,
            'total_liabilities' => round($totalLiabilities, 2),
            'equity' => $equity,
            'net_profit' => $netProfit,
            'total_equity' => round($totalEquity, 2),
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.05,
        ];
    }

    /**
     * Get General Ledger running statements for an account.
     */
    public function getGeneralLedger(ChartOfAccount $account, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('entry', function ($q) {
                $q->where('status', 'posted');
            })
            ->with('entry');

        if ($fromDate) {
            $query->whereHas('entry', fn ($q) => $q->whereDate('date', '>=', $fromDate));
        }

        if ($toDate) {
            $query->whereHas('entry', fn ($q) => $q->whereDate('date', '<=', $toDate));
        }

        $lines = $query->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->select('journal_entry_lines.*')
            ->get();

        $running = (float) $account->opening_balance;
        $ledgerRows = [];

        foreach ($lines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($account->isDebitNormal()) {
                $running += ($debit - $credit);
            } else {
                $running += ($credit - $debit);
            }

            $ledgerRows[] = [
                'line' => $line,
                'entry' => $line->entry,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($running, 2),
            ];
        }

        return [
            'account' => $account,
            'opening_balance' => (float) $account->opening_balance,
            'rows' => $ledgerRows,
            'closing_balance' => round($running, 2),
        ];
    }
}
