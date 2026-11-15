# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 13 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Expense Balance Synchronization**:
   - Approved expenses immediately decrement the linked cash or bank account balance (`financial_accounts.current_balance`), ensuring accurate petty cash drawer visibility.
2. **Cancellation Balance Restitution**:
   - Deleting or cancelling an approved expense atomically restores the deducted funds back to the financial account before soft-deleting the voucher.
3. **Approval Workflow Security**:
   - Pending expense vouchers (`status = 'pending_approval'`) do not modify account balances until reviewed and authorized by an approved manager or owner.
4. **Receipt Attachments**:
   - Receipt files (PDF, JPG, PNG) are safely isolated into `tenants/{tenant_id}/expenses/` storage disk.
5. **No Active Blockers**:
   - All 105 automated tests pass cleanly with 550 assertions across all 13 completed phases. Code conforms strictly to Laravel Pint standards.
