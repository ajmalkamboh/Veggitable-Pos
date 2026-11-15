# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 12 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Wastage Cost Valuation & Inventory Depletion**:
   - Spoilage, drying shrinkage, transit damage, and cull loss calculate financial loss based on produce batch cost basis or master cost price, updating aggregate stock and depleting specific or FIFO batches.
2. **Approval Workflow Gating**:
   - Draft wastage vouchers (`status = 'pending_approval'`) do not deduct stock or affect inventory balances until explicitly approved by an authorized manager/owner.
3. **Produce Sorting & Regrading Architecture**:
   - Morning grading sessions (`produce_regradings`) accurately balance input vs yield output, accounting for sorting shrinkage and cull loss both in physical weights (kg) and accounting loss (PKR).
4. **No Active Blockers**:
   - All 98 automated tests pass cleanly with 521 assertions across all 12 completed phases. Code conforms strictly to Laravel Pint standards.
