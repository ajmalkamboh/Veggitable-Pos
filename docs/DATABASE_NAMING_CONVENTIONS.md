# DATABASE NAMING CONVENTIONS & SCHEMA RULES

## 1. Table Naming Conventions
- All table names must be in **lowercase**, in the **plural form**, and separated by underscores (`snake_case`).
  - Example: `tenants`, `branches`, `products`, `inventory_batches`, `journal_entries`, `journal_lines`.
- Pivot tables joining two entities must be singular, joined alphabetically by underscore:
  - Example: `branch_user`, `permission_role`.

## 2. Column Naming Conventions
- Primary Keys: Always `id` as `BIGINT UNSIGNED AUTO_INCREMENT` (or UUID where distributed identification is mandatory).
- Foreign Keys: Singular entity name followed by `_id`.
  - Example: `tenant_id`, `branch_id`, `product_id`, `user_id`, `account_id`.
- Tenant Identification:
  - Every tenant-owned table MUST contain `tenant_id` as a foreign key referencing `tenants.id` with `CASCADE` or `RESTRICT` depending on integrity rules.
  - An index MUST be placed on `tenant_id` (either standalone or as the leading column of a compound index).
- Boolean Flags: Must start with `is_`, `has_`, or `can_`.
  - Example: `is_active`, `is_posted`, `is_taxable`, `has_batches`.
- Timestamps: Standard Laravel `created_at` and `updated_at` (nullable `deleted_at` when soft deletes are explicitly permitted). Financial vouchers and ledger lines must never use soft-deletes to conceal alterations.
- Monetary Amounts & Quantities:
  - Financial amounts: `DECIMAL(15, 2)` (or `DECIMAL(18, 4)` for multi-currency exchange rates).
  - Quantities (weights in KG/Grams): `DECIMAL(12, 3)` (to accommodate fractional weights such as `0.250 KG`).

## 3. Indexes & Constraints
- Compound Unique Indexes:
  - When a code or slug is unique per tenant, the unique index MUST include `tenant_id`:
    - `UNIQUE KEY unique_tenant_product_code (tenant_id, code)`
    - `UNIQUE KEY unique_tenant_customer_phone (tenant_id, phone)`
    - `UNIQUE KEY unique_tenant_invoice_number (tenant_id, invoice_number)`
- Foreign Key Constraints:
  - `tenant_id` -> `tenants(id) ON DELETE CASCADE`
  - Financial line references -> `ON DELETE RESTRICT` (ensures audit integrity)

