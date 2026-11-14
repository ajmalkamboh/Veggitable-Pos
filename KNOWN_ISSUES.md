# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 04 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Singleton Registration for Multi-Tenancy**:
   - `TenantContext` and `TenantManager` remain singletons in `AppServiceProvider`.
2. **Central Gate Interceptor**:
   - `Gate::before()` interceptor defined in `AppServiceProvider` routes all authorization checks through `$user->hasPermission($ability)` for regular staff while granting super admins and business owners overarching rights without breaking model policies.
3. **Compound Unique Indexing on Roles**:
   - Composite unique key `(tenant_id, slug)` permits multiple tenants to create custom roles sharing identical slugs without database collision while maintaining unique slugs within a tenant.
4. **Immutable System Roles**:
   - Pre-seeded system roles (`is_system = true`, `tenant_id = null`) are protected in `RoleController` from modification or deletion by tenant owners.
5. **Session & Rate Limiting Security**:
   - Login attempts are throttled by email and IP address (5 attempts per minute). Suspended users or users belonging to suspended businesses are prevented from authenticating and have their sessions immediately invalidated.
