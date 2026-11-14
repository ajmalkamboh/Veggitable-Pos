# KNOWN ISSUES & TECHNICAL DEBT TRACKER

## Phase 01 Status: No Active Blockers

### Notes & Observations:
1. **Windows WAMP MySQL Service**:
   - The Windows Service `wampmysqld64` requires elevation to start via standard PowerShell `Start-Service`.
   - Started MySQL 8.4.7 via mysqld background process directly with `--defaults-file=C:\wamp64\bin\mysql\mysql8.4.7\my.ini`.
   - Working as expected for local development.

2. **Tailwind Elimination**:
   - The default Laravel skeleton included Tailwind references in `vite.config.js` and `package.json`.
   - Removed Tailwind dependence in application views in favor of Bootstrap 5.3 and EasyAdmin Pro.
   - Vite is ready to bundle scripts if desired, or use direct public assets.

3. **Urdu Nastaliq Rendering in PDFs**:
   - Standard DomPDF does not include Nastaliq glyph shaping out-of-the-box.
   - For Phase 16 (Thermal printing and PDF reporting), we will configure appropriate TrueType Unicode fonts (such as DejaVu Sans, Noto Sans Arabic, or Jameel Noori TTF) or server-side image canvas rendering for 80mm thermal receipt printers.
