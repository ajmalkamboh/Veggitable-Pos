# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 03 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Singleton Registration for Multi-Tenancy**:
   - `TenantContext` and `TenantManager` remain singletons in `AppServiceProvider`.
2. **Compound Unique Indexing on Per-Tenant Codes**:
   - Multiple tenants continue to independently have branch code `HQ-01` or record code `REC-001` without collision due to composite unique keys `(tenant_id, code)`.
3. **Sequential Invoice Generation**:
   - Invoices use `INV-YYYY-XXXX-RAND` format generated inside atomic database transactions (`SubscriptionService::generateInvoiceNumber`).
4. **Subscription Limits & Feature Guarding**:
   - Feature entitlement middleware (`tenant.feature:<feature>`) and limit assertions (`assertCanAddBranch`, `assertCanAddUser`) throw `Symfony\Component\HttpKernel\Exception\HttpException` with HTTP status code 403 when violated.
5. **Soft Deletion of Plans with Active Subscribers**:
   - If a Super Admin attempts to delete a plan that still has active subscribers, the system automatically deactivates (`is_active = false`) the plan rather than hard-deleting, preserving referential integrity.
