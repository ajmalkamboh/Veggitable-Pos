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
- `parent_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `categories(id)` ON DELETE SET NULL (Self-referencing hierarchy)
- `name`: VARCHAR(100) NOT NULL
- `name_ur`: VARCHAR(100) NULL
- `slug`: VARCHAR(100) NOT NULL
- `type`: VARCHAR(30) NOT NULL DEFAULT 'product' (INDEXED) (`product`, `expense`)
- `description`: TEXT NULL
- `image_path`: VARCHAR(255) NULL
- `is_active`: BOOLEAN NOT NULL DEFAULT 1
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, type, slug)`

### `unit_conversions`
Wholesale lot and bulk packaging multipliers to base retail weight units.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `from_unit_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `units(id)` ON DELETE CASCADE
- `to_unit_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `units(id)` ON DELETE CASCADE
- `multiplier`: DECIMAL(12, 4) NOT NULL (e.g. 1 Mann -> 40.0000 Kg)
- `description`: VARCHAR(100) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, from_unit_id, to_unit_id)`

### `products`
Master catalog of vegetables, fruits, Mandi auction lots, and pre-packaged goods.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `category_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `categories(id)` ON DELETE SET NULL
- `subcategory_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `categories(id)` ON DELETE SET NULL
- `name`: VARCHAR(255) NOT NULL
- `name_ur`: VARCHAR(255) NULL
- `code`: VARCHAR(50) NOT NULL
- `barcode`: VARCHAR(50) NULL
- `type`: VARCHAR(30) NOT NULL DEFAULT 'vegetable' (INDEXED) (`vegetable`, `fruit`, `mandi_lot`, `prepack`)
- `purchase_unit_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `units(id)` ON DELETE SET NULL
- `selling_unit_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `units(id)` ON DELETE SET NULL
- `stock_unit_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `units(id)` ON DELETE SET NULL
- `unit_conversion_factor`: DECIMAL(12, 4) NOT NULL DEFAULT 1.0000
- `cost_price`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `selling_price`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `min_selling_price`: DECIMAL(12, 2) NOT NULL DEFAULT 0.00
- `reorder_level`: DECIMAL(12, 2) NOT NULL DEFAULT 10.00
- `is_perishable`: BOOLEAN NOT NULL DEFAULT 1
- `shelf_life_days`: INT NOT NULL DEFAULT 3
- `image_path`: VARCHAR(255) NULL
- `is_active`: BOOLEAN NOT NULL DEFAULT 1
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, code)`, `INDEX(tenant_id, barcode)`, `INDEX(tenant_id, type, is_active)`

### `suppliers`
Subzi Mandi commission agents (Aarthi), direct farm growers, and wholesale suppliers.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(255) NOT NULL
- `name_ur`: VARCHAR(255) NULL
- `code`: VARCHAR(50) NOT NULL
- `type`: VARCHAR(30) NOT NULL DEFAULT 'commission_agent' (INDEXED) (`commission_agent`, `farmer`, `wholesaler`, `other`)
- `contact_person`: VARCHAR(100) NULL
- `phone`: VARCHAR(50) NULL
- `mobile`: VARCHAR(50) NULL
- `mandi_stall_no`: VARCHAR(100) NULL
- `address`: TEXT NULL
- `city`: VARCHAR(100) NULL
- `commission_rate`: DECIMAL(5, 2) NOT NULL DEFAULT 0.00
- `opening_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `current_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `bank_name`: VARCHAR(100) NULL
- `bank_account_title`: VARCHAR(100) NULL
- `bank_account_number`: VARCHAR(50) NULL
- `payment_terms_days`: INT NOT NULL DEFAULT 7
- `is_active`: BOOLEAN NOT NULL DEFAULT 1 (INDEXED)
- `notes`: TEXT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, code)`, `INDEX(tenant_id, type, is_active)`, `INDEX(tenant_id, phone)`

### `customers`
Retail walk-in accounts, regular hotels, restaurants, catering services, and wholesale buyers.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `name`: VARCHAR(255) NOT NULL
- `name_ur`: VARCHAR(255) NULL
- `code`: VARCHAR(50) NOT NULL
- `type`: VARCHAR(30) NOT NULL DEFAULT 'retail' (INDEXED) (`retail`, `hotel_restaurant`, `wholesale_buyer`, `canteen_caterer`)
- `contact_person`: VARCHAR(100) NULL
- `phone`: VARCHAR(50) NULL
- `mobile`: VARCHAR(50) NULL
- `address`: TEXT NULL
- `city`: VARCHAR(100) NULL
- `tax_id`: VARCHAR(50) NULL (NTN / CNIC)
- `credit_limit`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `credit_days`: INT NOT NULL DEFAULT 0
- `opening_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `current_balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `is_active`: BOOLEAN NOT NULL DEFAULT 1 (INDEXED)
- `notes`: TEXT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, code)`, `INDEX(tenant_id, type, is_active)`, `INDEX(tenant_id, phone)`

### `party_ledger_entries`
Audit trail and financial statement lines for suppliers and customer accounts.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `party_type`: VARCHAR(20) NOT NULL (INDEXED) (`supplier`, `customer`)
- `party_id`: BIGINT UNSIGNED NOT NULL (INDEXED)
- `date`: DATE NOT NULL (INDEXED)
- `reference_type`: VARCHAR(50) NULL (`opening_balance`, `purchase`, `sale`, `payment`, `receipt`, `adjustment`)
- `reference_id`: BIGINT UNSIGNED NULL
- `reference_no`: VARCHAR(100) NULL
- `description`: VARCHAR(255) NOT NULL
- `debit`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `credit`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `balance`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, party_type, party_id, date)`

---

## 4. Mandi Purchases & Procurement Tables (Phase 09)

### `purchase_invoices`
Mandi procurement bills, weighing scales, commissions, and supplier payables.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `supplier_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `suppliers(id)` ON DELETE CASCADE
- `invoice_no`: VARCHAR(50) NOT NULL (e.g. `PUR-2026-0001`)
- `supplier_invoice_no`: VARCHAR(50) NULL (Mandi vendor parchi reference)
- `date`: DATE NOT NULL (INDEXED)
- `due_date`: DATE NULL
- `status`: VARCHAR(20) NOT NULL DEFAULT 'received' (`received`, `pending`, `cancelled`)
- `payment_status`: VARCHAR(20) NOT NULL DEFAULT 'unpaid' (`unpaid`, `partial`, `paid`)
- `subtotal`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Produce line totals)
- `commission_rate`: DECIMAL(5, 2) NOT NULL DEFAULT 0.00 (Arhti % fee)
- `commission_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `mandi_fee`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Chungi / Market Committee fee)
- `freight_charges`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Transport / Mazdoori)
- `other_charges`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `discount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `total_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `paid_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `financial_account_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `financial_accounts(id)` ON DELETE SET NULL
- `notes`: TEXT NULL
- `attachment_path`: VARCHAR(255) NULL (Photo of physical Mandi auction slip)
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, invoice_no)`, `INDEX(tenant_id, branch_id, date)`, `INDEX(tenant_id, supplier_id, payment_status)`

### `purchase_invoice_items`
Individual vegetable and fruit line items with weighing scale gross/tare deduction.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `purchase_invoice_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `purchase_invoices(id)` ON DELETE CASCADE
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE RESTRICT
- `unit_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `units(id)` ON DELETE SET NULL
- `quantity`: DECIMAL(15, 3) NOT NULL (Number of bags/crates or standard unit count)
- `gross_weight`: DECIMAL(15, 3) NULL (Vehicle/scale gross weight in kg)
- `tare_weight`: DECIMAL(15, 3) NULL (Empty crate/packaging weight deduction in kg)
- `net_weight`: DECIMAL(15, 3) NULL (Billed net produce weight: `gross - tare`)
- `unit_price`: DECIMAL(15, 2) NOT NULL
- `subtotal`: DECIMAL(15, 2) NOT NULL
- `lot_number`: VARCHAR(50) NULL (Batch identifier for FIFO tracking)
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, purchase_invoice_id)`, `INDEX(tenant_id, product_id, lot_number)`

### `purchase_returns`
Debit notes against purchase invoices for rejected/spoiled produce.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `purchase_invoice_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `purchase_invoices(id)` ON DELETE CASCADE
- `supplier_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `suppliers(id)` ON DELETE CASCADE
- `return_no`: VARCHAR(50) NOT NULL (e.g. `PR-2026-0001`)
- `date`: DATE NOT NULL (INDEXED)
- `reason`: VARCHAR(255) NOT NULL
- `total_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `refund_status`: VARCHAR(20) NOT NULL DEFAULT 'adjusted' (`adjusted`, `refunded`)
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, return_no)`, `INDEX(tenant_id, purchase_invoice_id)`

### `purchase_return_items`
Produce returned to Mandi suppliers with rate and subtotal.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `purchase_return_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `purchase_returns(id)` ON DELETE CASCADE
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE RESTRICT
- `quantity`: DECIMAL(15, 3) NOT NULL
- `unit_price`: DECIMAL(15, 2) NOT NULL
- `subtotal`: DECIMAL(15, 2) NOT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL

---

## 5. Super Admin, Roles & Security Tables
- `subscription_plans`, `subscriptions`, `subscription_invoices`, `roles`, `permissions`, `permission_role`, `role_user`, `audit_logs`.
