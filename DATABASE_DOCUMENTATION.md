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

## 5. Inventory & Stock Management Tables (Phase 10)

### `inventory_stocks`
Real-time aggregate stock balance per product per branch outlet.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `quantity`: DECIMAL(15, 3) NOT NULL DEFAULT 0.000 (Current real-time stock balance in stock unit)
- `cost_price`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Current average cost)
- `reorder_level`: DECIMAL(15, 3) NOT NULL DEFAULT 0.000 (Branch-specific low-stock alert threshold)
- `last_restocked_at`: TIMESTAMP NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, branch_id, product_id)`, `INDEX(tenant_id, branch_id)`, `INDEX(tenant_id, product_id)`

### `inventory_batches`
Traceable produce lots for First-In, First-Out (FIFO) costing and perishable shelf-life tracking.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `purchase_invoice_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `purchase_invoices(id)` ON DELETE SET NULL
- `purchase_invoice_item_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `purchase_invoice_items(id)` ON DELETE SET NULL
- `lot_number`: VARCHAR(50) NOT NULL (e.g. `LOT-TOM-2601-01`)
- `received_date`: DATE NOT NULL
- `expiry_date`: DATE NULL (Computed from product perishable shelf life)
- `initial_quantity`: DECIMAL(15, 3) NOT NULL
- `current_quantity`: DECIMAL(15, 3) NOT NULL
- `unit_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `status`: VARCHAR(20) NOT NULL DEFAULT 'active' (`active`, `depleted`, `expired`, `cancelled`)
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `INDEX(tenant_id, branch_id, product_id, status)`, `INDEX(tenant_id, lot_number)`

### `stock_movements`
Immutable double-entry stock movement ledger for complete inventory auditability.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `batch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `inventory_batches(id)` ON DELETE SET NULL
- `type`: VARCHAR(30) NOT NULL (`purchase_in`, `sale_out`, `transfer_out`, `transfer_in`, `adjustment_in`, `adjustment_out`, `wastage_out`)
- `reference_type`: VARCHAR(50) NULL (`purchase_invoice`, `pos_sale`, `stock_transfer`, `stock_adjustment`)
- `reference_id`: BIGINT UNSIGNED NULL
- `reference_no`: VARCHAR(100) NULL
- `quantity_change`: DECIMAL(15, 3) NOT NULL (Positive for inward, negative for outward)
- `quantity_before`: DECIMAL(15, 3) NOT NULL
- `quantity_after`: DECIMAL(15, 3) NOT NULL
- `unit_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `notes`: TEXT NULL
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, branch_id, product_id, created_at)`, `INDEX(tenant_id, type)`

### `stock_transfers` & `stock_transfer_items`
Inter-branch logistics vouchers and line items for transfer between branches.
- `stock_transfers`: `id`, `tenant_id`, `transfer_no` (UNIQUE), `from_branch_id`, `to_branch_id`, `transfer_date`, `status` (`pending`, `in_transit`, `received`, `cancelled`), `dispatched_at`, `dispatched_by`, `received_at`, `received_by`, `vehicle_number`, `driver_name`, `driver_phone`, `notes`, `created_by`, timestamps, soft deletes.
- `stock_transfer_items`: `id`, `stock_transfer_id`, `tenant_id`, `product_id`, `batch_id`, `quantity_dispatched`, `quantity_received`, `unit_cost`, timestamps.

### `stock_adjustments` & `stock_adjustment_items`
Physical stock count audits and variance reconciliation vouchers.
- `stock_adjustments`: `id`, `tenant_id`, `branch_id`, `adjustment_no` (UNIQUE), `date`, `reason` (`physical_count_discrepancy`, `drying_weight_loss`, `damage_spoilage`, `excess_produce_found`, `other`), `notes`, `created_by`, timestamps.
- `stock_adjustment_items`: `id`, `stock_adjustment_id`, `tenant_id`, `product_id`, `current_quantity`, `counted_quantity`, `difference_quantity`, `unit_cost`, timestamps.

---

## 6. POS & Sales Management Tables (Phase 11)

### `pos_sessions`
Cashier terminal work shifts and till cash drawer reconciliations.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `user_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `users(id)` ON DELETE CASCADE
- `opened_at`: TIMESTAMP NOT NULL
- `closed_at`: TIMESTAMP NULL
- `opening_cash`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `closing_cash`: DECIMAL(15, 2) NULL
- `expected_cash`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `discrepancy`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `total_sales_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `total_transactions_count`: INT NOT NULL DEFAULT 0
- `status`: VARCHAR(20) NOT NULL DEFAULT 'open' (`open`, `closed`)
- `notes`: TEXT NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, branch_id, user_id, status)`

### `sales_invoices`
POS checkout retail and wholesale sales invoices with comprehensive profitability metrics.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `pos_session_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `pos_sessions(id)` ON DELETE SET NULL
- `customer_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `customers(id)` ON DELETE SET NULL
- `invoice_no`: VARCHAR(50) NOT NULL (e.g. `INV-2601-00001`)
- `invoice_date`: DATE NOT NULL
- `subtotal`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `tax_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `discount_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `net_total`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `paid_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `due_amount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `change_returned`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `total_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Real-time FIFO COGS)
- `gross_profit`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Net Total - Total Cost)
- `status`: VARCHAR(20) NOT NULL DEFAULT 'completed' (`completed`, `partial_return`, `returned`, `cancelled`)
- `notes`: TEXT NULL
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, invoice_no)`, `INDEX(tenant_id, branch_id, invoice_date)`, `INDEX(tenant_id, customer_id)`

### `sales_invoice_items`
Produce line items per sales invoice with cost basis and discounts.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `sales_invoice_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `sales_invoices(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `quantity`: DECIMAL(15, 3) NOT NULL
- `unit_code`: VARCHAR(20) NOT NULL DEFAULT 'kg'
- `unit_price`: DECIMAL(15, 2) NOT NULL
- `cost_price`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (FIFO cost basis)
- `subtotal`: DECIMAL(15, 2) NOT NULL
- `discount`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `tax`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `net_total`: DECIMAL(15, 2) NOT NULL
- `notes`: VARCHAR(255) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL

### `sales_invoice_payments`
Multi-tender split payments (Cash, Bank, Card, Credit).
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `sales_invoice_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `sales_invoices(id)` ON DELETE CASCADE
- `payment_method`: VARCHAR(30) NOT NULL (`cash`, `bank`, `card`, `credit`)
- `amount`: DECIMAL(15, 2) NOT NULL
- `account_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `accounts(id)` ON DELETE SET NULL
- `transaction_reference`: VARCHAR(100) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL

### `held_sales_tickets`
Parked shopping carts for rapid cashier lane throughput.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `user_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `users(id)` ON DELETE CASCADE
- `ticket_number`: VARCHAR(50) NOT NULL
- `customer_name`: VARCHAR(255) NULL
- `cart_data`: JSON NOT NULL
- `subtotal`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `status`: VARCHAR(20) NOT NULL DEFAULT 'held' (`held`, `resumed`, `cancelled`)
- `created_at`, `updated_at`: TIMESTAMP NULL

### `sales_returns` & `sales_return_items`
Itemized and full returns with inventory restock and refund vouchers.
- `sales_returns`: `id`, `tenant_id`, `branch_id`, `sales_invoice_id`, `return_no` (UNIQUE), `return_date`, `refund_amount`, `refund_method` (`cash`, `credit_note`, `customer_balance`), `reason`, `created_by`, timestamps.
- `sales_return_items`: `id`, `sales_return_id`, `tenant_id`, `sales_invoice_item_id`, `product_id`, `quantity`, `refund_price`, `subtotal`, `is_restocked`, timestamps.

---

## 7. Produce Wastage, Damage & Regrading Tables (Phase 12)

### `wastage_records`
Produce spoilage, drying shrinkage, transit damage, and pest infestation loss vouchers.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `wastage_no`: VARCHAR(50) NOT NULL (e.g. `WST-2601-00001`)
- `date`: DATE NOT NULL
- `reason`: VARCHAR(50) NOT NULL DEFAULT 'spoilage' (`spoilage`, `drying_shrinkage`, `handling_damage`, `pest_damage`, `trimming_cleaning`, `expired`, `other`)
- `status`: VARCHAR(20) NOT NULL DEFAULT 'approved' (`pending_approval`, `approved`, `rejected`)
- `total_items_count`: INT UNSIGNED NOT NULL DEFAULT 0
- `total_quantity`: DECIMAL(15, 3) NOT NULL DEFAULT 0.000 (Total kg wasted)
- `total_loss_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Total PKR financial loss)
- `approved_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `approved_at`: TIMESTAMP NULL
- `rejection_reason`: VARCHAR(255) NULL
- `notes`: TEXT NULL
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- `deleted_at`: TIMESTAMP NULL (Soft Deletes)
- **Indexes**: `UNIQUE(tenant_id, wastage_no)`, `INDEX(tenant_id, branch_id, date)`, `INDEX(tenant_id, status)`

### `wastage_record_items`
Produce line items within each wastage voucher with loss valuation and disposal method.
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `wastage_record_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `wastage_records(id)` ON DELETE CASCADE
- `product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `batch_id`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `inventory_batches(id)` ON DELETE SET NULL
- `quantity`: DECIMAL(15, 3) NOT NULL
- `unit_code`: VARCHAR(20) NOT NULL DEFAULT 'kg'
- `unit_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Historical cost basis)
- `total_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Financial loss = quantity * unit_cost)
- `disposal_method`: VARCHAR(50) NOT NULL DEFAULT 'discarded' (`discarded`, `donated`, `composted`, `salvaged`)
- `notes`: VARCHAR(255) NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `INDEX(tenant_id, product_id)`

### `produce_regradings`
Produce sorting, quality grading, and downgrade sessions (e.g. morning sorting of Grade A produce into Grade B with cull loss).
- `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `tenant_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `tenants(id)` ON DELETE CASCADE
- `branch_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `branches(id)` ON DELETE CASCADE
- `regrading_no`: VARCHAR(50) NOT NULL (e.g. `RGD-2601-00001`)
- `date`: DATE NOT NULL
- `source_product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `source_quantity`: DECIMAL(15, 3) NOT NULL (Input weight in kg)
- `target_product_id`: BIGINT UNSIGNED NOT NULL, FOREIGN KEY -> `products(id)` ON DELETE CASCADE
- `target_quantity`: DECIMAL(15, 3) NOT NULL (Output graded weight in kg)
- `wastage_quantity`: DECIMAL(15, 3) NOT NULL DEFAULT 0.000 (Culled/lost weight = source - target)
- `source_unit_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `target_unit_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00
- `loss_cost`: DECIMAL(15, 2) NOT NULL DEFAULT 0.00 (Financial loss = wastage_quantity * source_unit_cost)
- `notes`: TEXT NULL
- `created_by`: BIGINT UNSIGNED NULL, FOREIGN KEY -> `users(id)` ON DELETE SET NULL
- `created_at`, `updated_at`: TIMESTAMP NULL
- **Indexes**: `UNIQUE(tenant_id, regrading_no)`, `INDEX(tenant_id, branch_id, date)`

---

## 8. Super Admin, Roles & Security Tables
- `subscription_plans`, `subscriptions`, `subscription_invoices`, `roles`, `permissions`, `permission_role`, `role_user`, `audit_logs`.
