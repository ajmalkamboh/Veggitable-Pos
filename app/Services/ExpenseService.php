<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Create an expense voucher and optionally deduct cash/bank balance.
     */
    public function createExpense(
        Tenant $tenant,
        Branch $branch,
        User $user,
        array $data,
        ?UploadedFile $attachment = null
    ): Expense {
        return DB::transaction(function () use ($tenant, $branch, $user, $data, $attachment) {
            $prefix = 'EXP-'.date('ym');
            $lastExpense = Expense::where('tenant_id', $tenant->id)
                ->where('expense_no', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastExpense && preg_match('/-(\d+)$/', $lastExpense->expense_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $expenseNo = sprintf('%s-%05d', $prefix, $nextNumber);

            $attachmentPath = null;
            if ($attachment) {
                $attachmentPath = $attachment->store("tenants/{$tenant->id}/expenses", 'public');
            }

            $status = $data['status'] ?? 'approved';
            $isApproved = ($status === 'approved');
            $amount = (float) $data['amount'];

            $expense = Expense::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'category_id' => $data['category_id'],
                'financial_account_id' => $data['financial_account_id'] ?? null,
                'expense_no' => $expenseNo,
                'date' => $data['date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payee_name' => $data['payee_name'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'attachment_path' => $attachmentPath,
                'description' => $data['description'] ?? null,
                'status' => $status,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
                'approved_by' => $isApproved ? $user->id : null,
                'approved_at' => $isApproved ? now() : null,
                'created_by' => $user->id,
            ]);

            // Deduct balance from account if approved immediately
            if ($isApproved && ! empty($data['financial_account_id'])) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->find($data['financial_account_id']);
                if ($account) {
                    $account->decrement('current_balance', $amount);
                }
            }

            AuditLog::log(
                event: 'expense.created',
                action: "Logged expense '{$expense->expense_no}' for Rs. {$amount} [{$expense->payment_method}]",
                auditable: $expense
            );

            return $expense->load(['category', 'branch', 'financialAccount', 'creator']);
        });
    }

    /**
     * Approve a pending expense voucher and deduct cash/bank balance.
     */
    public function approve(Expense $expense, User $approver): Expense
    {
        return DB::transaction(function () use ($expense, $approver) {
            if ($expense->status !== 'pending_approval') {
                throw new \DomainException(__('Only pending expense vouchers can be approved.'));
            }

            if ($expense->financial_account_id) {
                $account = FinancialAccount::where('tenant_id', $expense->tenant_id)->find($expense->financial_account_id);
                if ($account) {
                    $account->decrement('current_balance', (float) $expense->amount);
                }
            }

            $expense->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditLog::log(
                event: 'expense.approved',
                action: "Approved expense '{$expense->expense_no}' for Rs. {$expense->amount}",
                auditable: $expense
            );

            return $expense;
        });
    }

    /**
     * Reject a pending expense voucher.
     */
    public function reject(Expense $expense, User $rejecter, string $reason): Expense
    {
        if ($expense->status !== 'pending_approval') {
            throw new \DomainException(__('Only pending expense vouchers can be rejected.'));
        }

        $expense->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $rejecter->id,
            'approved_at' => now(),
        ]);

        AuditLog::log(
            event: 'expense.rejected',
            action: "Rejected expense '{$expense->expense_no}' with reason: {$reason}",
            auditable: $expense
        );

        return $expense;
    }

    /**
     * Cancel / delete expense voucher and refund account balance if previously deducted.
     */
    public function cancel(Expense $expense, User $user): void
    {
        DB::transaction(function () use ($expense) {
            if ($expense->status === 'approved' && $expense->financial_account_id) {
                $account = FinancialAccount::where('tenant_id', $expense->tenant_id)->find($expense->financial_account_id);
                if ($account) {
                    $account->increment('current_balance', (float) $expense->amount);
                }
            }

            AuditLog::log(
                event: 'expense.cancelled',
                action: "Cancelled expense '{$expense->expense_no}' for Rs. {$expense->amount}",
                auditable: $expense
            );

            $expense->delete();
        });
    }
}
