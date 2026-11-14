# DATABASE DOCUMENTATION: VEGGITABLE SAAS

**Database System**: MySQL 8.4.7 Community Server  
**Database Name**: `veggitable_saas`  
**Default Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`

---

## 1. Tables Implemented (Phases 01 & 02)

### `tenants`
The core business entity for the SaaS platform.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name`: VARCHAR(255) NOT NULL
- `slug`: VARCHAR(100) NOT NULL UNIQUE
- `domain`: VARCHAR(255) NULL UNIQUE (for custom domains)
- `subdomain`: VARCHAR(100) NULL UNIQUE (for subdomain-based tenant routing)
- `status`: VARCHAR(30) NOT NULL DEFAULT 'active' (INDEXED) (`active`, `suspended`, `expired`)
- `email`: VARCHAR(255) NULL
- `phone`: VARCHAR(50) NULL
- `address`: VARCHAR(255) NULL
- `city`: VARCHAR(100) NULL
- `currency`: VARCHAR(10) NOT NULL DEFAULT 'PKR'
- `timezone`: VARCHAR(50) NOT NULL DEFAULT 'Asia/Karachi'
- `default_locale`: VARCHAR(10) NOT NULL DEFAULT 'en'
- `settings`: JSON NULL (Fiscal year start, receipt formatting, tax settings)
- `trial_ends_at`: TIMESTAMP NULL
- `subscription_ends_at`: TIMESTAMP NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)

### `branches`
Multiple branch outlets per tenant (shops, subzi mandi counters, cold storage warehouses).
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(255) NOT NULL
- `code`: VARCHAR(50) NOT NULL (e.g. `HQ-01`, `BR-02`)
- `phone`: VARCHAR(50) NULL
- `email`: VARCHAR(255) NULL
- `address`: TEXT NULL
- `city`: VARCHAR(100) NULL
- `is_headquarters`: BOOLEAN NOT NULL DEFAULT 0
- `is_active`: BOOLEAN NOT NULL DEFAULT 1 (INDEXED)
- `settings`: JSON NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL
- **Indexes**:
  - `UNIQUE(tenant_id, code)`: Ensures unique branch code within a tenant while permitting same code across different tenants.
  - `INDEX(tenant_id, is_active)`

### `users`
System users, platform administrators, tenant owners, and branch staff.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE (NULL for Platform Super Admin)
- `current_branch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `branches(id)` ON DELETE SET NULL
- `name`: VARCHAR(255) NOT NULL
- `email`: VARCHAR(255) NOT NULL UNIQUE
- `email_verified_at`: TIMESTAMP NULL
- `password`: VARCHAR(255) NOT NULL
- `is_super_admin`: BOOLEAN NOT NULL DEFAULT 0
- `is_tenant_owner`: BOOLEAN NOT NULL DEFAULT 0
- `status`: VARCHAR(30) NOT NULL DEFAULT 'active'
- `phone`: VARCHAR(50) NULL
- `preferences`: JSON NULL
- `remember_token`: VARCHAR(100) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, status)`

### `branch_user`
Pivot table defining user branch assignments with default branch indicators.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `user_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `users(id)` ON DELETE CASCADE
- `is_default`: BOOLEAN NOT NULL DEFAULT 0
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**:
  - `UNIQUE(branch_id, user_id)`
  - `INDEX(tenant_id, user_id)`

### `tenant_records`
Sample tenant-owned business entity demonstrating strict data isolation.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `branches(id)` ON DELETE SET NULL
- `name`: VARCHAR(255) NOT NULL
- `code`: VARCHAR(50) NOT NULL
- `description`: TEXT NULL
- `amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `metadata`: JSON NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL
- **Indexes**:
  - `UNIQUE(tenant_id, code)`
  - `INDEX(tenant_id, branch_id)`

### System & Infrastructure Tables:
- `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
