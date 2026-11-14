# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 02 Status: No Active Blockers

### Notes & Verifications:
1. **Singleton Registration for TenantContext**:
   - Resolved during testing: `TenantContext` and `TenantManager` must be bound as singletons in `AppServiceProvider` so that Eloquent model event listeners (`BelongsToTenant::bootBelongsToTenant`) receive the exact resolved request context rather than fresh uninitialized instances.
2. **Compound Unique Indexing on Per-Tenant Codes**:
   - Verified: Multiple tenants can independently have branch code `HQ-01` or record code `REC-001` without collision due to composite unique keys `(tenant_id, code)`.
3. **HTTP 403 on Multi-Tenant Violations**:
   - Verified: `CrossTenantAccessException`, `TenantSuspendedException`, and `TenantExpiredException` extend `Symfony\Component\HttpKernel\Exception\HttpException` with HTTP status code 403.
