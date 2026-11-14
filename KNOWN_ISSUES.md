# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 05 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Onboarding Gating Middleware**:
   - `EnsureTenantOnboarded` intercepts non-onboarded tenants attempting to trade or record transactions, redirecting them smoothly to the 4-step wizard.
   - Platform Super Admin and administrative context switchers (`/tenant/branch/{branch}/switch`, `/tenant/switch/{tenant}`) correctly bypass onboarding gating.
2. **Preset Defaults for Produce Shops**:
   - Industry-standard units (`kg`, `g`, `bori`, `crate`, `dz`, `pc`) and categories (Fresh Leafy, Roots, Fruiting, Fruits, Mandi lots, and common operational expenses) are pre-populated in Urdu & English.
3. **Compound Unique Indexing on Categories & Units**:
   - Composite keys `(tenant_id, code)` on units and `(tenant_id, type, slug)` on categories ensure complete isolation between tenants.
4. **Historical Immutability**:
   - Tenants can edit profile metadata, add branches, and manage categories post-onboarding without disrupting previously posted invoices or ledgers.
