# DATABASE DOCUMENTATION: VEGGITABLE SAAS

**Database System**: MySQL 8.4.7 Community Server  
**Database Name**: `veggitable_saas`  
**Default Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`

---

## 1. Current Tables (Phase 01)

### `migrations`
Standard Laravel migration tracking table.

### `users`
Foundation user authentication table (will be enhanced with `tenant_id` and role attributes in Phase 02 and 04).
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name`: VARCHAR(255) NOT NULL
- `email`: VARCHAR(255) NOT NULL UNIQUE
- `email_verified_at`: TIMESTAMP NULL
- `password`: VARCHAR(255) NOT NULL
- `remember_token`: VARCHAR(100) NULL
- `created_at`: TIMESTAMP NULL
- `updated_at`: TIMESTAMP NULL

### `password_reset_tokens`
- `email`: VARCHAR(255) PRIMARY KEY
- `token`: VARCHAR(255) NOT NULL
- `created_at`: TIMESTAMP NULL

### `sessions`
Database-driven HTTP session storage.
- `id`: VARCHAR(255) PRIMARY KEY
- `user_id`: BIGINT UNSIGNED NULL (INDEXED)
- `ip_address`: VARCHAR(45) NULL
- `user_agent`: TEXT NULL
- `payload`: LONGTEXT NOT NULL
- `last_activity`: INT NOT NULL (INDEXED)

### `cache` & `cache_locks`
Database cache store tables.

### `jobs`, `job_batches`, `failed_jobs`
Database queue workers and asynchronous jobs tables.

---

## 2. Planned Schema Architecture (Phase 02+)

### Multi-Tenancy Core:
- `tenants`: Primary SaaS tenant record (slug, business name, domain, status, plan_id, trial_ends_at, settings).
- `branches`: Branches per tenant (tenant_id, name, code, phone, address, is_headquarters, is_active).
- `subscription_plans`: Platform subscription tiers (code, name, price, billing_interval, limits_json, features_json).
- `tenant_subscriptions`: Tenant billing cycles, invoices, and payments.

### Perishable Vegetables & Products:
- `product_categories`: Category classification (tenant_id, name_en, name_ur, code).
- `products`: Product master (tenant_id, category_id, name_en, name_ur, code, barcode, stock_unit_id, min_stock).
- `unit_conversions`: Product-specific conversion definitions (e.g., 1 Crate = 20.000 KG).
- `inventory_batches`: Perishable batch tracking with FIFO cost (tenant_id, branch_id, product_id, batch_no, purchase_date, unit_cost, quantity_in, quantity_remaining).

### Double-Entry Accounting:
- `chart_of_accounts`: Tenant account chart (tenant_id, code, name, type, parent_id).
- `journal_entries`: Balanced transaction headers (tenant_id, branch_id, voucher_type, voucher_no, date, is_posted).
- `journal_lines`: Double-entry lines (tenant_id, journal_entry_id, account_id, debit, credit, narration).
