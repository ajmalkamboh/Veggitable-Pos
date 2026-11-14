# DATABASE DOCUMENTATION: VEGGITABLE SAAS

**Database System**: MySQL 8.4.7 Community Server  
**Database Name**: `veggitable_saas`  
**Default Character Set**: `utf8mb4`  
**Collation**: `utf8mb4_unicode_ci`

---

## 1. Character Encoding & Multilingual Storage (Phase 06)
- **Character Set**: `utf8mb4` ensures 4-byte UTF-8 encoding supporting Arabic, Urdu Nastaliq glyphs, emojis, and extended symbols.
- **Collation**: `utf8mb4_unicode_ci` provides full case-insensitive and accent-insensitive matching across Urdu characters (e.g. `ک`, `گ`, `ٹ`, `ڈ`, `ڑ`, `ں`, `ے`, `ہ`, `ۂ`).
- **Bilingual Strategy**:
  - Model attributes maintain dual fields where user-facing text is required: `name` (English/General), `name_ur` (Urdu).
  - Search queries utilize `App\Core\Localization\BilingualSearchable::scopeSearchBilingual` to evaluate matches across both language columns simultaneously.

---

## 2. Multi-Tenant Core Tables (Phases 01 & 02)

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
- `onboarding_completed`: BOOLEAN NOT NULL DEFAULT 0 (INDEXED)
- `onboarding_step`: INT NOT NULL DEFAULT 1
- `logo_path`: VARCHAR(255) NULL
- `financial_year_start`: VARCHAR(10) NOT NULL DEFAULT '07-01'
- `business_opening_date`: DATE NULL
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
- `preferences`: JSON NULL (e.g. `{"locale": "ur"}`)
- `remember_token`: VARCHAR(100) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, status)`

---

## 3. Onboarding & Master Configuration Tables (Phase 05)

### `financial_accounts`
Cash registers/tills and shop commercial bank accounts per tenant.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `branches(id)` ON DELETE SET NULL
- `name`: VARCHAR(100) NOT NULL
- `type`: VARCHAR(20) NOT NULL DEFAULT 'cash' (INDEXED) (`cash`, `bank`)
- `bank_name`: VARCHAR(100) NULL
- `account_number`: VARCHAR(50) NULL
- `opening_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `current_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `is_active`: BOOLEAN NOT NULL DEFAULT 1
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, type)`

### `units`
Measurement units for wholesale lots and retail counter sales.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(50) NOT NULL (e.g. "Kilogram", "Sack / Bori")
- `name_ur`: VARCHAR(50) NULL (e.g. "کلوگرام", "بوری")
- `code`: VARCHAR(20) NOT NULL (e.g. `kg`, `g`, `crate`, `bori`, `dz`, `pc`)
- `is_fractional`: BOOLEAN NOT NULL DEFAULT 1
- `is_system`: BOOLEAN NOT NULL DEFAULT 0
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, code)`

### `categories`
Master product classifications and operational expense accounts.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(100) NOT NULL
- `name_ur`: VARCHAR(100) NULL
- `slug`: VARCHAR(100) NOT NULL
- `type`: VARCHAR(30) NOT NULL DEFAULT 'product' (INDEXED) (`product`, `expense`)
- `description`: TEXT NULL
- `is_active`: BOOLEAN NOT NULL DEFAULT 1
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, type, slug)`

---

## 4. Super Admin, Roles & Security Tables
- `subscription_plans`, `subscriptions`, `subscription_invoices`, `roles`, `permissions`, `permission_role`, `role_user`, `audit_logs`.
