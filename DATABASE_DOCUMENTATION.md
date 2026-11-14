# DATABASE DOCUMENTATION: VEGGITABLE SAAS

**Database System**: MySQL 8.4.7 Community Server  
**Database Name**: `veggitable_saas`  
**Default Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`

---

## 1. Multi-Tenant Core Tables (Phases 01 & 02)

### `tenants`
The central business entity for the SaaS platform.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `plan_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `subscription_plans(id)` ON DELETE SET NULL
- `current_subscription_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `subscriptions(id)` ON DELETE SET NULL
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
- `settings`: JSON NULL
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
- **Indexes**: `UNIQUE(tenant_id, code)`, `INDEX(tenant_id, is_active)`

### `users`
System users, platform administrators, tenant owners, and branch staff.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `current_branch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `branches(id)` ON DELETE SET NULL
- `name`: VARCHAR(255) NOT NULL
- `email`: VARCHAR(255) NOT NULL UNIQUE
- `email_verified_at`: TIMESTAMP NULL
- `password`: VARCHAR(255) NOT NULL
- `is_super_admin`: BOOLEAN NOT NULL DEFAULT 0
- `is_tenant_owner`: BOOLEAN NOT NULL DEFAULT 0
- `status`: VARCHAR(30) NOT NULL DEFAULT 'active' (`active`, `suspended`, `inactive`)
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
- **Indexes**: `UNIQUE(branch_id, user_id)`, `INDEX(tenant_id, user_id)`

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
- **Indexes**: `UNIQUE(tenant_id, code)`, `INDEX(tenant_id, branch_id)`

---

## 2. Super Admin & Subscription Tables (Phase 03)

### `subscription_plans`
Defines available subscription plans, pricing tiers, limits, and feature entitlements.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name`: VARCHAR(100) NOT NULL
- `slug`: VARCHAR(50) NOT NULL UNIQUE
- `description`: TEXT NULL
- `price_monthly`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `price_yearly`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `trial_days`: INT NOT NULL DEFAULT 14
- `grace_days`: INT NOT NULL DEFAULT 3
- `max_branches`: INT NOT NULL DEFAULT 1 (-1 for unlimited)
- `max_users`: INT NOT NULL DEFAULT 2 (-1 for unlimited)
- `max_products`: INT NOT NULL DEFAULT 100 (-1 for unlimited)
- `features`: JSON NULL
- `is_active`: BOOLEAN NOT NULL DEFAULT 1 (INDEXED)
- `is_popular`: BOOLEAN NOT NULL DEFAULT 0
- `sort_order`: INT NOT NULL DEFAULT 0
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL

### `subscriptions`
Tenant subscription contracts tracking billing cycle, dates, and active status.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `plan_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `subscription_plans(id)` ON DELETE RESTRICT
- `billing_cycle`: VARCHAR(30) NOT NULL DEFAULT 'monthly'
- `status`: VARCHAR(30) NOT NULL DEFAULT 'trialing'
- `trial_starts_at`, `trial_ends_at`, `starts_at`, `ends_at`, `grace_ends_at`: TIMESTAMP NULL
- `price_paid`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `currency`: VARCHAR(10) NOT NULL DEFAULT 'PKR'
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, status)`

### `subscription_invoices`
Financial invoices generated for subscription payments and renewals.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `subscription_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `subscriptions(id)` ON DELETE SET NULL
- `plan_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `subscription_plans(id)` ON DELETE SET NULL
- `invoice_number`: VARCHAR(50) NOT NULL UNIQUE (e.g. `INV-2026-0001-ABCD`)
- `amount`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `currency`: VARCHAR(10) NOT NULL DEFAULT 'PKR'
- `payment_method`: VARCHAR(50) NOT NULL DEFAULT 'manual'
- `payment_status`: VARCHAR(30) NOT NULL DEFAULT 'paid'
- `transaction_reference`: VARCHAR(100) NULL
- `paid_at`: TIMESTAMP NULL
- `notes`: TEXT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, payment_status)`, `INDEX(invoice_number)`

---

## 3. Roles, Permissions & Security Tables (Phase 04)

### `roles`
Stores system-wide template roles (`tenant_id = null`) and tenant-custom roles (`tenant_id NOT NULL`).
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(100) NOT NULL
- `slug`: VARCHAR(50) NOT NULL
- `description`: TEXT NULL
- `is_system`: BOOLEAN NOT NULL DEFAULT 0
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, slug)`

### `permissions`
Granular platform capabilities catalog.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name`: VARCHAR(100) NOT NULL
- `slug`: VARCHAR(100) NOT NULL UNIQUE
- `module`: VARCHAR(50) NOT NULL (INDEXED) (`pos`, `inventory`, `purchases`, `expenses`, `accounting`, `reports`, `users`, `settings`)
- `description`: TEXT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL

### `permission_role`
Pivot mapping permissions to roles.
- `role_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `roles(id)` ON DELETE CASCADE
- `permission_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `permissions(id)` ON DELETE CASCADE
- **Primary Key**: `[role_id, permission_id]`

### `role_user`
Pivot table assigning roles to users within a tenant.
- `user_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `users(id)` ON DELETE CASCADE
- `role_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `roles(id)` ON DELETE CASCADE
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Primary Key**: `[user_id, role_id]`
- **Indexes**: `INDEX(tenant_id, user_id)`

### `audit_logs`
Security and operational audit journal.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `user_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `action`: VARCHAR(100) NOT NULL (INDEXED) (e.g. `auth.login`, `auth.logout`, `user.created`, `role.updated`)
- `ip_address`: VARCHAR(45) NULL
- `user_agent`: TEXT NULL
- `payload`: JSON NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, created_at)`

---

## 4. System & Infrastructure Tables
- `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
