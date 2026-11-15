# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 10 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **FIFO Stock Batch Deduction**:
   - Outward stock transfers and inventory deductions automatically allocate from the oldest active produce lots first (`received_date asc`), ensuring accurate produce age tracking and shelf-life compliance.
2. **Real-time Inventory Synchronization**:
   - Inward purchases, cancellations, branch transfers, and physical audits atomically update both aggregate `inventory_stocks` and detailed `stock_movements`, guaranteeing zero balance drift.
3. **Multi-Branch Isolation in Transfers**:
   - Transfers validate origin branch stock, deduct origin stock on dispatch (marking `in_transit`), and replenish destination stock with new lots upon destination receipt acknowledgment.
4. **Physical Count Variance Handling**:
   - Physical audit sessions record previous system stock and counted physical stock, logging explicit gain or loss movements without overwriting history.
