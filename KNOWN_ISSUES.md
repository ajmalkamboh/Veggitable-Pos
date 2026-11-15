# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 15 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Real-time Dashboard Metrics**:
   - Executive dashboard queries calculate real-time turnover, procurement volume, current stock valuation, daily wastage loss, operational expenses, and till cash/bank balances with sub-second response times.
2. **Branch Isolation & Consolidated Views**:
   - The dashboard and all analytical reports support instant outlet-level filtering as well as multi-branch consolidated organization-wide views.
3. **Daily Cash Register Z-Report Reconciliation**:
   - Accurately reconciles sales cash intake against cash payouts for mandi lots and operational petty vouchers, ensuring transparent drawer closing before safe deposit.
4. **Strict Tenant Data Isolation**:
   - All reporting endpoints and metric aggregations enforce tenant context through global scopes and tenant-scoped query constraints. Tenant A cannot see any data or metrics belonging to Tenant B.
5. **No Active Blockers**:
   - All 119 automated tests pass cleanly with 638 assertions across all 15 completed phases. Code conforms strictly to Laravel Pint standards.
