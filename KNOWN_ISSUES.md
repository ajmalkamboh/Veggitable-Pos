# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 11 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **FIFO Costing & Profit Margin Calculation**:
   - Every produce sale depletes the oldest active batches first and captures historical cost price directly into `sales_invoice_items.cost_price`, allowing exact Gross Profit and COGS calculation in real time.
2. **Customer Credit Limit Gating**:
   - Checkout with partial or full `credit` payment enforces `$customer->credit_limit` validation (`current_balance + credit_due <= credit_limit`). If exceeded, the transaction is rejected with an actionable validation error.
3. **Cashier Till Variance Tracking**:
   - `PosSession` tracks opening cash, sales payments, cash drops, and calculates discrepancy on close (`closing_cash - expected_cash`), ensuring drawer accountability.
4. **Restocking on Sales Returns**:
   - Returned produce items can be conditionally restocked (`is_restocked = true`), which immediately increments `inventory_stocks`, appends a `sale_return` inward movement to `stock_movements`, and reinstates quantity into available batches.
5. **No Active Blockers**:
   - All 91 automated tests pass cleanly with 489 assertions. Code conforms strictly to Laravel Pint standards.
