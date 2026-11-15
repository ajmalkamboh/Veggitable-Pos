# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 07 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **Model Parameter Cross-Tenant Protection**:
   - `PreventCrossTenantAccess` middleware automatically introspects all incoming route model parameters with `tenant_id` and blocks cross-tenant access with 403 Forbidden, protecting products, categories, units, and custom resources from URL and ID manipulation.
2. **Barcode & Packaging Standards**:
   - Standard retail in-store prefix `20` + 4-digit tenant identifier + 6-digit sequential/random digits ensures zero barcode collisions between tenants.
   - 50mm × 35mm thermal sticker view formatted for direct printing to barcode label printers.
3. **Wholesale to Retail Unit Conversion Formula**:
   - Multiplier stored as decimal (12, 4) allows exact representation for auction sacks (Mann = 40.0000, Peti = 15.0000, Katta = 50.0000) for inventory stock deductions in Phase 09 and Phase 10.
