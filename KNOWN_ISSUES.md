# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 08 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Party Ledger Audit Integrity**:
   - `party_ledger_entries` records all financial interactions (opening balances, supplier payments, customer receipts) with explicit debit, credit, running balance, and user creator timestamps.
   - Deleting suppliers or customers with non-zero balances is strictly prohibited to safeguard accounting integrity.
2. **Real-time Cash Drawer & Bank Account Synchronization**:
   - Payments to suppliers decrement the selected cash till/bank account and debit the supplier payable balance atomically in a database transaction.
   - Receipts from customers increment the financial account and credit the customer receivable balance atomically.
3. **Credit Limit Safety Controls**:
   - Customer accounts support strict credit limits and payment windows (credit days) with visual utilization gauges on profile screens.
