# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 06 Status: Complete & Verified — No Active Blockers

### Notes & Verifications:
1. **RTL Bootstrap 5.3 & Nastaliq Font Rendering**:
   - `bootstrap.rtl.min.css` dynamically conditionally loads when `app()->getLocale() === 'ur'`.
   - `custom-rtl.css` ensures floating elements, right-sidebars, dropdown offsets, and table alignments render natively in right-to-left layout without character disconnection.
2. **Numbers & Formatting Integrity**:
   - Currency values, invoice numbers, barcode references, and phone numbers remain stored as Western Arabic digits (`1,250.00`) to guarantee mathematical precision and POS scanner compatibility.
3. **Locale Priority Cascade**:
   - Verified: (1) Session, (2) User preference, (3) Tenant default, (4) App configuration.
4. **Bilingual Search Scope**:
   - Verified: `BilingualSearchable::scopeSearchBilingual` matches both English and Urdu queries across `name` and `name_ur` columns.
