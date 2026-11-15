# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 09 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Tare Weight Deduction Accuracy**:
   - Mandi scales record gross weight with crates/packaging; tare weight deduction calculates `net_weight = max(0, gross_weight - tare_weight)`. The line item total dynamically uses net weight when weighed or crate/unit count when sold by unit.
2. **Mandi Ancillary Charges Computation**:
   - Mandi Commission (Arhti fee %), Market Committee Fee (Chungi), and Transport/Labour (Mazdoori) are correctly summed and reflected in both the purchase invoice total and the supplier payable balance.
3. **Immediate Cash Settlement vs. Credit Term Billing**:
   - Optional immediate settlement atomically decrements the business financial account (e.g. main cash till) and logs payment debit to the supplier's running ledger, maintaining double-entry integrity.
4. **Debit Notes & Cancellation Reversals**:
   - Debit notes for rejected or spoiled produce deduct the supplier balance and post ledger debits.
   - Cancelling an invoice atomically reverses supplier credit, refunds financial accounts if paid, and posts balancing ledger adjustments.
