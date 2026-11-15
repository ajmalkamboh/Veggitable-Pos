# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 14 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Strict Double-Entry Balancing**:
   - Every journal entry validates that Total Debits equal Total Credits. Any discrepancy throws an exception before database insertion.
2. **Account Balance Directionality**:
   - `ChartOfAccount` balances update dynamically based on the normal balance side of each account class (Assets and Expenses increase on Debit; Liabilities, Equity, and Revenue increase on Credit).
3. **Automated Reversal Vouchers**:
   - Reversing a posted journal voucher produces an offsetting transaction (`REV-JV-XXXXX`) flipping debits and credits, safely restoring previous account balances.
4. **Trial Balance & Profit and Loss Precision**:
   - Real-time Trial Balance and Income Statement accurately compute Operating Revenue, Cost of Goods Sold, Gross Margin, and Net Profit.
5. **No Active Blockers**:
   - All 112 automated tests pass cleanly with 577 assertions across all 14 completed phases. Code conforms strictly to Laravel Pint standards.
