# IMPLEMENTATION LOG

## Phase 01: Project Foundation & Technical Architecture
**Date**: 2026-11-14
- Established Laravel 13 / 12 core on PHP 8.3 with MySQL 8.4 via WAMP.
- Integrated EasyAdmin Pro template with full assets and created `custom-rtl.css` for Urdu typography.
- Installed `laravel/boost`, `livewire/livewire`, `barryvdh/laravel-dompdf`, and `maatwebsite/excel`.
- Built modular layouts, Blade components, English/Urdu translations, and architectural blueprints in `docs/`.
- All Phase 01 tests passed.

---

## Phase 02: SaaS Foundation & Multi-Tenant Architecture
**Date**: 2026-11-15
- Multi-Tenancy Engine Core (`app/Core/MultiTenancy`): `TenantContext`, `TenantScope`, `BelongsToTenant`, `BelongsToBranch`, `TenantManager`.
- Tables & Models: `tenants`, `branches`, `users`, `branch_user`, `tenant_records`.
- Atomic Registration & Middlewares: `TenantRegistrationService`, `IdentifyTenant`, `EnsureTenantActive`, `PreventCrossTenantAccess`.
- All Phase 02 tests passed.

---

## Phase 03: Super Admin Panel & Subscription Management
**Date**: 2026-11-15
- Multi-Tier Subscription & Invoicing Schema: `subscription_plans`, `subscriptions`, `subscription_invoices`, and tenant foreign keys.
- Super Admin Controllers: `DashboardController`, `TenantController`, `SubscriptionPlanController`, `SubscriptionPaymentController`, `ReportController`.
- Services & Middlewares: `SubscriptionService`, `EnsureSuperAdmin`, `EnsureTenantHasFeature`.
- Seeders: `SubscriptionPlanSeeder` (4 tiers: Basic, Standard, Professional, Enterprise).
- All Phase 03 tests passed.

---

## Phase 04: Authentication, Roles & Permissions
**Date**: 2026-11-15
- Multi-Tenant Roles & Permissions Schema: `roles`, `permissions`, `permission_role`, `role_user`, `audit_logs`.
- Controllers: `LoginController`, `PasswordResetController`, `UserController`, `RoleController`, `AuditLogController`.
- Central authorization interceptor in `AppServiceProvider`.
- All Phase 04 tests passed.

---

## Phase 05: Business Onboarding & Initial Configuration
**Date**: 2026-11-15
- 4-Step Onboarding Wizard: `OnboardingController` (`step1`, `step2`, `step3`, `step4`, `finish`).
- Schema: Onboarding fields on `tenants`, `financial_accounts`, `units`, `categories`.
- Service: `TenantOnboardingService` provisioning units, categories, and accounts.
- Middleware: `EnsureTenantOnboarded` gating operational routes until setup is complete.
- All Phase 05 tests passed.

---

## Phase 06: Urdu/English Localization & RTL Support
**Date**: 2026-11-15

### Actions Executed:
1. **Bilingual Translation Dictionaries (`lang/en` & `lang/ur`)**:
   - `common.php`: Extended system-wide terms, actions, and status translations.
   - `shop.php`: Dedicated vegetable shop vocabulary (vegetables, fruits, mandi auction lots, cashier, weighing, units, net payable, change return, drawer cash).
   - `onboarding.php`: Complete 4-step wizard bilingual titles, checklist labels, and instruction text.
   - `validation.php`: Authentic Urdu validation error messages for form submissions.
2. **Dynamic Locale Management & Middleware Priority**:
   - `LocaleController`: Persists selected locale in session and automatically updates authenticated user's `preferences['locale']`.
   - `SetLocale` Middleware: Resolves locale via strict hierarchy: (1) Session, (2) User preference, (3) Active tenant default locale, (4) App fallback.
3. **RTL / LTR Layouts & Typography**:
   - `layouts.admin` & `layouts.auth`: Dynamically output `dir="rtl"` / `dir="ltr"` and conditionally include `bootstrap.rtl.min.css` alongside `custom-rtl.css`.
   - `custom-rtl.css`: Complete styling rules for right-aligned sidebars, mirrored icon spacing, floating header offsets, input group radius corrections, and `Noto Nastaliq Urdu` / `Noto Sans Arabic` typography.
4. **Bilingual Search Engine**:
   - `App\Core\Localization\BilingualSearchable`: Trait providing `scopeSearchBilingual` to search across English and Urdu columns (`name`, `name_ur`) seamlessly in MySQL `utf8mb4_unicode_ci`.
   - Integrated into `Category` and `Unit` models.
5. **Print & Thermal Receipt**:
   - `resources/views/reports/thermal-receipt.blade.php`: RTL thermal receipt print template supporting 80mm roll dimensions, Urdu header, line items, and totals.
6. **Automated Testing Suite**:
   - `tests/Feature/Phase06LocalizationAndRtlTest.php`: 7 test cases covering locale switching, user preference persistence, tenant default fallback, RTL HTML attributes & CSS inclusion, LTR HTML attributes & CSS inclusion, Urdu dictionary resolution, and bilingual SQL searches.
   - Complete application test suite: **53 tests passed, 235 assertions, 0 failures**.
   - Code formatted with `vendor/bin/pint --format agent`.

---

## [Phase 07] - Product, Categories & Unit Management
**Date**: 2026-11-15
**Status**: COMPLETED

1. **Database Schema & Migrations**:
   - `2026_11_14_220000_add_parent_and_image_to_categories_table.php`: Added `parent_id` (foreign key self-reference) and `image_path` to `categories` table.
   - `2026_11_14_220010_create_unit_conversions_table.php`: Created `unit_conversions` table (`tenant_id`, `from_unit_id`, `to_unit_id`, `multiplier`, `description`).
   - `2026_11_14_220020_create_products_table.php`: Created `products` table (`tenant_id`, `category_id`, `subcategory_id`, `name`, `name_ur`, `code`, `barcode`, `type`, `purchase_unit_id`, `selling_unit_id`, `stock_unit_id`, `unit_conversion_factor`, `cost_price`, `selling_price`, `min_selling_price`, `reorder_level`, `is_perishable`, `shelf_life_days`, `image_path`, `is_active`, timestamps, soft deletes).
2. **Models & Traits**:
   - `Product`: Relationships (`category`, `subcategory`, `purchaseUnit`, `sellingUnit`, `stockUnit`), `getProfitMarginPercentageAttribute()`, `generateBarcode(Tenant $tenant)` generator.
   - `UnitConversion`: Conversion formula execution `convert($quantity)`.
   - `Unit`: Bidirectional conversion relationships (`conversionsFrom`, `conversionsTo`) and products relationship.
   - `Category`: Hierarchy relationships (`parent`, `subcategories`, `products`).
   - `AuditLog`: Added `log($event, $action, $auditable, $oldValues, $newValues)` helper.
3. **Multi-Tenant Security & Controller Implementation**:
   - `PreventCrossTenantAccess`: Upgraded to automatically inspect all incoming route parameters with `tenant_id` and block cross-tenant inspection with 403.
   - `CategoryController`: Full CRUD for product and expense categories, subcategory assignment, bilingual support, image upload, protected by `categories.manage` permission and tenant verification.
   - `UnitController`: Measurement unit CRUD + conversion multiplier management (e.g. 1 Mann = 40 Kg), protected by `units.manage`.
   - `ProductController`: Catalog management, filtering by type/category/status, bilingual search, profit margin calculation, shelf life monitoring, barcode sticker printing sheet, protected by `products.view` and `products.manage`.
4. **Views & Thermal Printing**:
   - `tenant/categories/index.blade.php`, `create.blade.php`, `edit.blade.php`.
   - `tenant/units/index.blade.php`, `create.blade.php`, `edit.blade.php`.
   - `tenant/products/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `barcode.blade.php`.
   - Sidebar updated with Vegetables & Fruits menu.
5. **Testing & Verification**:
   - `Phase07ProductCategoryUnitManagementTest`: 9 test cases verifying category hierarchy, unit conversions, barcode generation, profit margin computation, bilingual search, tenant isolation, and role permissions.
   - Complete application test suite: **62 tests passed, 278 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

---

## [Phase 08] - Supplier & Customer Management
**Date**: 2026-11-15
**Status**: COMPLETED

1. **Database Schema & Migrations**:
   - `2026_11_15_043025_create_suppliers_table.php`: Created `suppliers` table with vendor classification (`commission_agent`, `farmer`, `wholesaler`, `other`), Mandi stall number, commission rate %, bank account details, credit terms, opening balance, current balance, soft deletes.
   - `2026_11_15_043032_create_customers_table.php`: Created `customers` table with account classification (`retail`, `hotel_restaurant`, `wholesale_buyer`, `canteen_caterer`), credit limit amount, allowed credit days, opening balance, current receivable balance, soft deletes.
   - `2026_11_15_043039_create_party_ledger_entries_table.php`: Created `party_ledger_entries` table (`tenant_id`, `party_type`, `party_id`, `date`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `balance`, `created_by`).
2. **Models & Relationships**:
   - `Supplier`: `tenant()`, `ledgerEntries()` (`party_type = 'supplier'`), `generateCode(Tenant $tenant)` sequential code generator.
   - `Customer`: `tenant()`, `ledgerEntries()` (`party_type = 'customer'`), `generateCode(Tenant $tenant)` sequential code generator.
   - `PartyLedgerEntry`: `tenant()`, `creator()` (User audit).
   - `Tenant`: Updated with `suppliers()`, `customers()`, `partyLedgerEntries()`.
3. **Controllers & Operations**:
   - `SupplierController`: Full vendor CRUD, Mandi stall tracking, commission rates, opening balance ledger generation, manual payment processing from financial accounts with live balance updates.
   - `CustomerController`: Full customer account CRUD, credit limits, payment period days, opening balance ledger generation, payment receipt collection deposited into cash/bank accounts with live balance updates.
4. **Views & UI Integration**:
   - `tenant/suppliers/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php` (with statement table & payment modal).
   - `tenant/customers/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php` (with credit limit progress gauge & receipt modal).
   - Sidebar updated with "Suppliers & Customers" menu.
5. **Testing & Verification**:
   - `Phase08SupplierCustomerManagementTest`: 8 test cases covering opening balance ledger creation, supplier payment processing, customer receipt collection, credit limit enforcement, bilingual search, tenant isolation, and role permissions.
   - Complete application test suite: **70 tests passed, 326 assertions, 0 failures**.
   - Code formatted with Laravel Pint.


---

## [Phase 09] - Purchase Management
**Date**: 2026-11-15
**Status**: COMPLETED

1. **Database Schema & Migrations**:
   - `2026_11_15_044256_create_purchases_tables.php`:
     - `purchase_invoices`: (`tenant_id`, `branch_id`, `supplier_id`, `invoice_no`, `supplier_invoice_no`, `date`, `due_date`, `status`, `payment_status`, `subtotal`, `commission_rate`, `commission_amount`, `mandi_fee`, `freight_charges`, `other_charges`, `discount`, `total_amount`, `paid_amount`, `financial_account_id`, `notes`, `attachment_path`, `created_by`, timestamps, soft deletes).
     - `purchase_invoice_items`: (`purchase_invoice_id`, `tenant_id`, `product_id`, `unit_id`, `quantity`, `gross_weight`, `tare_weight`, `net_weight`, `unit_price`, `subtotal`, `lot_number`, timestamps).
     - `purchase_returns`: (`tenant_id`, `purchase_invoice_id`, `supplier_id`, `return_no`, `date`, `reason`, `total_amount`, `refund_status`, `created_by`, timestamps, soft deletes).
     - `purchase_return_items`: (`purchase_return_id`, `tenant_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, timestamps).
2. **Models & Business Calculations**:
   - `PurchaseInvoice`: Relationships (`branch`, `supplier`, `items`, `returns`, `financialAccount`, `creator`), `due_balance` and `due_amount` accessors, `generateInvoiceNumber(Tenant $tenant)` (`PUR-YYYY-XXXX`).
   - `PurchaseInvoiceItem`: Product lot tracking, tare weight scale deduction `net_weight = max(0, gross_weight - tare_weight)`.
   - `PurchaseReturn`: Debit note numbering `generateReturnNumber(Tenant $tenant)` (`PR-YYYY-XXXX`).
   - `Supplier` & `Tenant`: Added relationships `purchaseInvoices()` and `purchaseReturns()`.
3. **Controllers & Operations**:
   - `Tenant\PurchaseInvoiceController`:
     - `index`: Overview cards (Total Purchases, Outstanding Payables, Today's Purchases, Total Invoices), search and status filtering, pagination.
     - `create` & `store`: Dynamic multi-item lot entry, real-time gross/tare/net scale calculations, Mandi commission, Market fee (Chungi), Freight (Mazdoori) additions, immediate cash/bank settlement with account debit, supplier balance update, automated supplier ledger entry.
     - `show`: Detailed invoice inspection, item lots table, Mandi cost breakdown, payment settlement status, attachment preview, debit note modal.
     - `print`: A4 & thermal printable Mandi purchase voucher with bilingual English/Urdu headers, item breakdown, and purchaser/arhti signatures.
     - `destroy`: Atomic invoice cancellation reversing supplier balance, financial account balance (if paid), and recording cancellation ledger entry.
   - `Tenant\PurchaseReturnController`:
     - `store`: Records debit notes against received invoices for spoiled/rejected produce, deducts supplier payable, records debit entry in party ledger.
4. **Views & Menus**:
   - `resources/views/tenant/purchases/index.blade.php`, `create.blade.php`, `show.blade.php`, `print.blade.php`.
   - Sidebar updated with "Mandi Purchases" navigation.
5. **Testing & Verification**:
   - `Phase09PurchaseManagementTest`: 7 comprehensive test cases verifying invoice creation, tare weight calculations, Mandi commission & fees, immediate cash settlement, debit notes, invoice cancellation reversal, tenant isolation, and print rendering.
   - Complete application test suite: **77 tests passed, 379 assertions, 0 failures**.
   - Code formatted with Laravel Pint.


---

## [Phase 10] - Inventory & Stock Management
**Date**: 2026-11-15
**Status**: COMPLETED

1. **Database Schema & Migrations**:
   - `2026_11_15_050049_create_inventory_and_stock_tables.php`:
     - `inventory_stocks`: Real-time aggregate stock per product per branch (`quantity`, `cost_price`, `reorder_level`, `last_restocked_at`).
     - `inventory_batches`: Traceable produce lots (`lot_number`, `received_date`, `expiry_date`, `initial_quantity`, `current_quantity`, `unit_cost`, `status`).
     - `stock_movements`: Immutable stock movement audit ledger (`type`, `reference_type`, `reference_id`, `reference_no`, `quantity_change`, `quantity_before`, `quantity_after`, `unit_cost`, `created_by`).
     - `stock_transfers` & `stock_transfer_items`: Inter-branch dispatch & receipt logistics vouchers (`transfer_no`, `from_branch_id`, `to_branch_id`, `status`, vehicle & driver details).
     - `stock_adjustments` & `stock_adjustment_items`: Physical count audit and variance reconciliation vouchers (`adjustment_no`, `reason`, system stock, physical count, variance).
2. **Models & Services**:
   - Models: `InventoryStock`, `InventoryBatch`, `StockMovement`, `StockTransfer`, `StockTransferItem`, `StockAdjustment`, `StockAdjustmentItem`.
   - `App\Services\InventoryService`:
     - `recordPurchaseIn(PurchaseInvoice $invoice)`: Automatically provisions batches, updates branch stock, and records `purchase_in` movement.
     - `reversePurchaseIn(PurchaseInvoice $invoice)`: Reverses stock on cancellation and logs `adjustment_out`.
     - `recordPurchaseReturn(PurchaseReturn $return)`: Deducts returned stock and logs movement.
     - `dispatchTransfer(StockTransfer $transfer)`: Deducts source branch stock using FIFO lot allocation and logs `transfer_out`.
     - `receiveTransfer(StockTransfer $transfer, array $receivedQuantities)`: Adds destination branch stock, provisions lot batches, and logs `transfer_in`.
     - `recordAdjustment(Tenant $tenant, Branch $branch, array $validated)`: Reconciles physical counts against recorded stock and logs gain/loss audit movements.
3. **Controllers & Operations**:
   - `Tenant\InventoryController`:
     - `index`: Real-time stock overview table, total valuation metrics, branch filter, category filter, low-stock filter, search.
     - `batches`: Produce lots listing with received dates, expiry / shelf-life tracking, and active status.
     - `movements`: Immutable stock ledger with filterable movement types.
   - `Tenant\StockTransferController`:
     - `index`: Listing of transfers with status badges.
     - `create` & `store`: Transfer voucher dispatch form with dynamic item repeater.
     - `show` & `receive`: Transfer voucher view with destination receipt acknowledgment.
   - `Tenant\StockAdjustmentController`:
     - `index`: List of physical audits.
     - `create` & `store`: Full branch stock audit sheet with real-time variance calculation.
     - `show`: Detailed reconciliation variance report.
4. **Views & Menus**:
   - `resources/views/tenant/inventory/index.blade.php`, `batches.blade.php`, `movements.blade.php`.
   - `resources/views/tenant/inventory/transfers/index.blade.php`, `create.blade.php`, `show.blade.php`.
   - `resources/views/tenant/inventory/adjustments/index.blade.php`, `create.blade.php`, `show.blade.php`.
   - Sidebar updated with "Inventory & Stock" navigation section.
5. **Testing & Verification**:
   - `Phase10InventoryAndStockManagementTest`: 7 comprehensive test cases verifying purchase inward stocking, cancellation reversal, live valuation & low-stock alerts, inter-branch transfer lifecycle, physical count audit reconciliation, FIFO lot batch deduction, and tenant isolation.
   - Complete application test suite: **84 tests passed, 435 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 11: POS & Sales Management (Completed 2026-11-15)
1. **Database Schema & Migrations**:
   - `pos_sessions`: Daily cashier till tracking with opening cash, closing cash, expected cash calculated from cash sales, cash drops, discrepancy variance, and notes.
   - `sales_invoices`: Order header with invoice number (`INV-YYMM-XXXXX`), status (completed, partial_return, returned, cancelled), total amounts, line discounts, invoice-level discounts, tax, net total, paid amount, due balance, change returned, and profit metrics.
   - `sales_invoice_items`: Line-level produce details with quantity, unit code, unit price, cost price at time of sale, discount, total line price, and real-time Cost of Goods Sold (COGS) tracking.
   - `sales_invoice_payments`: Support for multi-payment splits across Cash, Bank Transfer, Card, and Customer Credit.
   - `held_sales_tickets`: Parking/holding in-progress shopping carts at peak hours with customer reference and JSON cart payload.
   - `sales_returns`: Customer return/refund vouchers linked to original invoice with refund method (cash, credit note, customer balance credit) and restock flags.
   - `sales_return_items`: Return line items with restock quantity, refund unit price, and restocked lot batch allocations.
2. **Business Models & Services**:
   - Models: `PosSession`, `SalesInvoice`, `SalesInvoiceItem`, `SalesInvoicePayment`, `HeldSalesTicket`, `SalesReturn`, `SalesReturnItem`.
   - `App\Services\PosService`:
     - `checkout(Tenant $tenant, Branch $branch, User $cashier, array $data, ?PosSession $session)`: Validates customer credit limit, deducts stock via FIFO batch allocation, logs outward stock movements (`sale`), creates payments, adjusts cashier cash drawer, and updates customer credit ledger.
     - `holdCart(Tenant $tenant, Branch $branch, User $cashier, array $data)`: Preserves shopping cart tickets for quick recall.
     - `processReturn(SalesInvoice $invoice, array $data, User $user)`: Processes line-item or full refunds, returns goods to inventory, generates `sale_return` stock movements, restores batch quantities, and updates customer ledger balances for credit refunds.
3. **Controllers & Operations**:
   - `Tenant\PosController`:
     - `counter`: High-speed touchscreen POS terminal with category filtering, instant produce search, barcode input, weight calculation, quick tender buttons, hold/resume modal, and credit customer selector.
     - `checkout`: Multi-payment processing and checkout transaction.
     - `holdCart` & `heldTickets`: Cart parking and retrieval APIs.
     - `receipt`: Dual 80mm/58mm thermal receipt rendering with bilingual Urdu/English thank you and company details.
   - `Tenant\SalesInvoiceController`:
     - `index`: Sales invoice registry with daily gross sales, net revenue, received cash, receivables, and profit margin cards.
     - `show`: Detailed invoice breakdown with line item cost, selling price, profit margin, and integrated return modal.
     - `returnItem`: Handler for partial/full sales return and inventory restocking.
   - `Tenant\PosSessionController`:
     - `open`: Initializes cashier drawer till with opening float.
     - `close`: Reconciles actual cash count against register calculated sales and records cash variance.
4. **Testing & Verification**:
   - `Phase11PosAndSalesManagementTest`: 7 comprehensive test cases verifying POS session open/close with variance calculation, checkout with real-time stock deduction and COGS profit calculation, walk-in cash transaction, credit customer checkout with ledger updates and limit rejection, cart hold & recall, partial item return with inventory restocking, and strict multi-tenant isolation.
   - Complete application test suite: **91 tests passed, 489 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 12: Wastage, Damage & Stock Adjustments (Completed 2026-11-15)
1. **Database Schema & Migrations**:
   - `wastage_records`: Spoilage and damage voucher tracking with sequential numbering (`WST-YYMM-XXXXX`), date, primary reason (`spoilage`, `drying_shrinkage`, `handling_damage`, `pest_damage`, `trimming_cleaning`, `expired`, `other`), status (`pending_approval`, `approved`, `rejected`), total items count, total quantity (kg), total financial loss cost (PKR), approver ID, approval timestamp, rejection reason, and inspection notes.
   - `wastage_record_items`: Itemized produce lines linking to product, batch/lot, wasted weight quantity, unit cost basis, calculated financial loss, disposal method (`discarded`, `donated`, `composted`, `salvaged`), and line notes.
   - `produce_regradings`: Produce sorting and quality grading sessions (`RGD-YYMM-XXXXX`) converting input source product (e.g. Grade A) to output target product (e.g. Grade B) while accounting for sorting cull loss and drying shrinkage.
2. **Business Models & Services**:
   - Models: `WastageRecord`, `WastageRecordItem`, `ProduceRegrading` with bilingual Urdu/English reason labels and status badges.
   - `App\Services\WastageService`:
     - `createWastage`: Records itemized wastage vouchers, computes financial cost valuation, and if approved immediately deducts stock and logs `wastage_out` stock movements.
     - `approve`: Workflow approval action for managers/owners which executes stock deductions and batch allocations on pending vouchers.
     - `reject`: Rejection action recording reason without inventory impact.
     - `regradeProduce`: Atomically deducts source produce stock, creates new target graded lot batch, logs shrinkage cull loss, and records audit trail.
3. **Controllers & Operations**:
   - `Tenant\WastageController`:
     - `index`: Summary cards (Month Loss PKR, Month Weight Lost kg, Pending Approvals, Total Vouchers), multi-criteria filtering, and voucher registry.
     - `create` & `store`: Interactive multi-item voucher entry with live stock hints, cost basis calculation, and approval workflow selection.
     - `show`: Itemized breakdown, disposal method badges, and manager approval/rejection modal.
     - `approve` & `reject`: Manager decision endpoints.
     - `regradingsIndex`, `regradingCreate` & `regradingStore`: Produce sorting and regrading management.
4. **Testing & Verification**:
   - `Phase12WastageAndDamageManagementTest`: 7 comprehensive test cases verifying approved wastage stock and batch deduction, pending approval workflow, manager rejection, produce sorting and regrading calculations, specific batch allocation, bilingual labels, and tenant isolation.
   - Complete application test suite: **98 tests passed, 521 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 13: Expense Management (Completed 2026-11-15)
1. **Database Schema & Migrations**:
   - `expenses`: Operational expense vouchers tracking sequential numbering (`EXP-YYMM-XXXXX`), date, branch, category ID, financial account ID, amount (PKR), payment method (`cash`, `bank`, `online`, `cheque`), payee/recipient name, reference/bill number, receipt attachment file path, description, status (`approved`, `pending_approval`, `rejected`), recurring flags, and approval audit timestamps.
2. **Business Models & Services**:
   - Model: `Expense` with multi-tenant scoping, bilingual payment method labels, and status badges.
   - `App\Services\ExpenseService`:
     - `createExpense`: Creates expense vouchers, handles receipt uploads to storage, and if approved immediately deducts funds from the selected financial account (`financial_accounts.current_balance`).
     - `approve`: Manager approval workflow deducting funds upon authorization.
     - `reject`: Rejection action recording reason without fund deduction.
     - `cancel`: Reverses expense, refunds balance back to cash/bank account, and soft-deletes the voucher.
3. **Controllers & Operations**:
   - `Tenant\ExpenseController`:
     - `index`: Summary cards (Month Total Expenses PKR, Today's Expenses PKR, Pending Approvals, Total Available Cash/Bank Reserves), multi-criteria filtering by category, branch, status, date, and search.
     - `create` & `store`: Comprehensive voucher creation form with live account balance hints, payment method selectors, receipt file upload, and recurring schedule toggles.
     - `show`: Voucher inspection, receipt attachment preview/download, account impact breakdown, and manager approval/rejection modal.
     - `approve`, `reject` & `destroy`: Decision and cancellation actions.
4. **Testing & Verification**:
   - `Phase13ExpenseManagementTest`: 7 comprehensive test cases verifying approved expense creation with account balance decrement, pending approval gating, manager rejection, voucher cancellation with balance refund, receipt attachment upload handling, tenant isolation, and bilingual labels.
   - Complete application test suite: **105 tests passed, 550 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 14: Financial Accounting & Ledgers (Completed 2026-11-15)
1. **Database Schema & Migrations**:
   - `chart_of_accounts`: Standard 5-tier accounting tree (`asset`, `liability`, `equity`, `revenue`, `expense`) with code, English name, Urdu name, subtype, opening balance, current running balance, system flag, and active status.
   - `journal_entries`: Double-entry journal voucher headers (`JV-YYMM-XXXXX`) with date, branch, reference type (`manual`, `purchase`, `sale`, `expense`, `wastage`, `transfer`), reference number, narration, total debit, total credit, status (`posted`, `draft`, `reversed`), and creator.
   - `journal_entry_lines`: Individual debit and credit rows linked to accounts with line memos.
2. **Business Models & Services**:
   - Models: `ChartOfAccount`, `JournalEntry`, `JournalEntryLine`.
   - `App\Services\AccountingService`:
     - `seedDefaultChartOfAccounts`: Auto-seeds 17 standard vegetable retail & wholesale accounts across all 5 classes.
     - `createJournalEntry`: Enforces strict double-entry balancing (`total_debits === total_credits`), generates sequential numbering, and updates account balances according to normal balance side (Debit normal for Assets/Expenses; Credit normal for Liabilities/Equity/Revenue).
     - `reverseJournalEntry`: Generates automated offsetting reversal vouchers flipping debits and credits, marking original voucher reversed and restoring account balances.
     - `getTrialBalance`: Compiles active account debit/credit balances and verifies system-wide double-entry equilibrium.
     - `getProfitAndLoss`: Real-time Income Statement computing Operating Revenue, Cost of Goods Sold, Gross Margin, Operating Expenses, and Net Profit.
     - `getBalanceSheet`: Statement of Financial Position verifying Assets = Liabilities + Equity (including net retained earnings).
     - `getGeneralLedger`: Account transaction ledger with running balance progression.
3. **Controllers & Operations**:
   - `Tenant\AccountingController`:
     - `coaIndex`, `coaCreate` & `coaStore`: Chart of accounts grouped view and custom account creation.
     - `journalIndex`, `journalCreate` & `journalStore`: Journal vouchers registry and interactive double-entry creation with live debit/credit balance indicator.
     - `journalShow` & `journalReverse`: Voucher inspection and reversal workflow.
     - `generalLedger`, `trialBalance`, `profitAndLoss` & `balanceSheet`: Core financial reporting suite.
4. **Testing & Verification**:
   - `Phase14FinancialAccountingTest`: 7 comprehensive test cases verifying auto-seeding of default chart of accounts, custom account creation, balanced JV posting with running balance updates, rejection of unbalanced vouchers, automated reversing entries, Trial Balance & P&L calculations, and multi-tenant isolation.
   - Complete application test suite: **112 tests passed, 577 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 15: Executive Dashboard & Business Reports (Completed 2026-11-15)
1. **Services & Business Logic**:
   - `App\Services\ReportingService`:
     - `getDashboardMetrics`: Compiles consolidated executive KPIs including Today's Sales, Transactions Count, Cash Collected, Credit Due, Month Sales, Month Gross Profit, Today/Month Mandi Purchases, Inventory Valuation (cost basis), Low Stock Alert Counts, Spoilage & Wastage Losses, Operating Expenses, and Total Available Cash & Bank Reserves. Features 7-day sales vs mandi procurement trends and 30-day top-selling produce rankings.
     - `getSalesReport`: Multi-parameter filtered sales analytics (by branch, customer, date range, status) with gross sales, discounts, tax, net turnover, collections, receivables, COGS, gross profit, and gross profit margin percentage.
     - `getPurchaseReport`: Mandi procurement analytics tracking lot produce subtotals, mandi commission agent fees, market committee charges, freight/mazdoori, total billing, and supplier payables.
     - `getDayEndRegisterReport`: Daily Cash Register Z-Report calculating total counter cash inflows, mandi cash procurement payouts, petty operating expenses, and net daily till cash movement.
2. **Controllers & Operations**:
   - `Tenant\DashboardController`: Main live executive command center with real-time operational KPI cards, branch context switcher, 7-day trend chart, top produce list, recent POS sales, and low stock warnings.
   - `Tenant\ReportController`:
     - `index`: Central Reports & Business Intelligence Hub linking to Sales Analytics, Mandi Procurement, Day-End Z-Report, P&L, Balance Sheet, and General Ledger.
     - `sales`: Filterable sales analytics report table with summary metrics and print layout.
     - `purchases`: Mandi wholesale lot procurement report table with surcharges breakdown.
     - `dayEnd`: Daily Cash Register Z-Report summary with cash movement audit ledger and safe deposit balance.
3. **Views & UI Components**:
   - `resources/views/tenant/dashboard.blade.php`: Modern EasyAdmin Pro dashboard with interactive metric tiles, CSS bar charts for 7-day trends, quick action shortcuts, and live alerts.
   - `resources/views/tenant/reports/index.blade.php`: Reports hub card catalog.
   - `resources/views/tenant/reports/sales.blade.php`: Sales turnover & gross margin report.
   - `resources/views/tenant/reports/purchases.blade.php`: Mandi procurement & fees report.
   - `resources/views/tenant/reports/day_end.blade.php`: Daily cash reconciliation Z-Report.
4. **Testing & Verification**:
   - `Phase15DashboardAndReportsTest`: 7 comprehensive test cases verifying dashboard metric calculations, branch filtering, reports hub accessibility, sales analytics calculations and filters, mandi procurement report calculations, day-end register Z-report cash reconciliation, and strict multi-tenant isolation.
   - Complete application test suite: **119 tests passed, 638 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 16: Printing, Receipts & Hardware Integration (Completed 2026-11-15)
1. **Services & Business Logic**:
   - `App\Services\HardwareIntegrationService`:
     - `parseWeightBarcode`: Decodes electronic produce scale EAN-13 barcodes (`20AAAAAWWWWW[C]`), extracting 5-digit PLU product code and 5-digit weight in grams into decimal kilograms.
     - `parsePriceBarcode`: Decodes price-embedded produce scale EAN-13 barcodes (`21AAAAAPPPPP[C]`).
     - `getEscPosCommands`: Compiles standard ESC/POS hex commands for cash drawer kick (pin 2 & pin 5), auto-paper cutting (full, partial, feed), bold font weight, double width/height, and text alignment.
     - `getDeviceProfiles`: Hardware catalog of tested electronic weighing scales (CAS, Mettler Toledo, DIGI, Rongta, Citizen) and thermal receipt printers (Epson, Rongta, Xprinter, Bixolon, Sunmi).
   - `App\Services\PrintService`:
     - Compiles and formats printing payloads for 10 core business documents: 80mm/58mm thermal receipts, A4 sales invoices, A4 mandi procurement bills, customer statements of account, supplier/aarthi statements, payment disbursement vouchers, receipt vouchers, stock valuation audit sheets, and wastage audit sheets. Includes `numberToWords` conversion for financial vouchers.
2. **Controllers & Operations**:
   - `Tenant\PrintController`:
     - `salesThermal`: 80mm/58mm thermal receipt rendering with produce weights, unit rates, item discounts, bill totals, tender split, customer remaining credit balance, and Urdu Nastaliq thank-you note.
     - `salesA4`: Professional A4 tax invoice layout with company header, buyer details, table of items, terms, and dual signatures.
     - `purchaseA4`: A4 Mandi procurement bill with lot produce items, commission agent fees, market committee tax, freight, and payables.
     - `customerStatement`: Customer running ledger statement with invoices, payments, and outstanding balances.
     - `supplierStatement`: Mandi commission agent / supplier ledger with procurement lots, commission deductions, and payables.
     - `paymentVoucher`: Cash/bank outflow disbursement voucher with amounts in figures and words.
     - `receiptVoucher`: Cash/bank money collection voucher with invoice reference and cashier signature.
     - `stockReport`: A4 physical inventory count and valuation audit sheet (cost and retail bases).
     - `wastageReport`: A4 produce spoilage, drying shrinkage, and cull loss audit sheet.
   - `Tenant\HardwareController`:
     - `index`: Device management center with printer roll selection (80mm vs 58mm vs A4), auto-cutter toggles, drawer kick toggles, scale serial port baud rates, and live barcode decoding tester.
     - `updateSettings`: Saves tenant hardware preferences to `tenants.settings['hardware']`.
     - `parseBarcode`: API endpoint for fast barcode scanning and weighing scale decoding verification.
     - `testPrint`: Diagnostic thermal test slip verifying UTF-8, ESC/POS, and Urdu characters.
3. **Views & Templates**:
   - `resources/views/tenant/printing/`: `sales_thermal.blade.php`, `sales_a4.blade.php`, `purchase_a4.blade.php`, `customer_statement.blade.php`, `supplier_statement.blade.php`, `payment_voucher.blade.php`, `receipt_voucher.blade.php`, `stock_sheet.blade.php`, `wastage_sheet.blade.php`.
   - `resources/views/tenant/hardware/`: `index.blade.php`, `test_print.blade.php`.
4. **Testing & Verification**:
   - `Phase16PrintingAndHardwareIntegrationTest`: 10 comprehensive test cases verifying 80mm thermal receipt rendering, A4 sales tax invoice, A4 mandi purchase bill, customer statement, supplier statement, payment and receipt vouchers with spelled-out amounts, stock sheet and wastage reports, EAN-13 weight/price barcode decoding, ESC/POS commands, hardware settings persistence, and tenant isolation enforcement.
   - Complete application test suite: **129 tests passed, 730 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 17: Notifications & SaaS Automation (Completed 2026-11-15)
1. **Database Schema & Migrations**:
   - `database/migrations/2026_11_15_124515_create_notifications_table.php`: Standard Laravel multi-channel notifications table enriched with `tenant_id` foreign key and composite index `[tenant_id, read_at]` for fast multi-tenant querying.
2. **Notification Classes (`app/Notifications/`)**:
   - `LowStockAlertNotification`: Dispatches high-priority alert when produce quantity falls below reorder threshold, identifying branch, current stock, and reorder point in English & Urdu.
   - `DailySalesSummaryNotification`: Evening digest compiling gross turnover, cash collected, credit receivables, transaction volume, and gross profit.
   - `DailyWastageSummaryNotification`: Daily cull and spoilage digest compiling total loss monetary cost, culling quantity in kilograms, and voucher count.
   - `CustomerBalanceAlertNotification`: Credit risk notification when customer overdue receivables exceed safety limit or credit threshold.
   - `SupplierPaymentReminderNotification`: Mandi commission agent (aarthi) payable alert detailing unpaid lots and balance.
   - `StockAdjustmentAlertNotification`: Audit alert for physical inventory count variance and stock reconciliations.
   - `SubscriptionExpiryNotification`: SaaS billing reminder triggered at 7, 3, and 1 days prior to subscription renewal date.
   - `SystemAlertNotification`: General platform and operational broadcast alert.
3. **Services & Business Logic (`App\Services\AutomationService`)**:
   - `checkLowStock`: Scans produce inventory against reorder levels across branches and dispatches notifications to business owners and inventory managers.
   - `sendDailySalesSummary`: Aggregates day's sales invoices, calculating net turnover, cash inflows, credit balances, and gross margins.
   - `sendDailyWastageSummary`: Aggregates approved spoilage vouchers and calculates cull quantities and financial losses.
   - `checkCustomerBalances`: Detects customer accounts with outstanding balances exceeding defined credit thresholds.
   - `checkSupplierPayables`: Detects supplier/aarthi accounts with unpaid mandi purchases.
   - `checkSubscriptionExpiries`: Scans active SaaS tenants nearing subscription or trial expiration dates.
   - `notifyStockAdjustment`: Notifies management of physical stock audit variances.
   - `sendSms` & `sendWhatsApp`: Gateway adapters saving configuration in tenant preferences without exposing credentials.
4. **Artisan Console Automation Commands**:
   - `veggitable:daily-summaries`: Scheduled command executing daily sales and wastage summaries for all active SaaS businesses.
   - `veggitable:check-subscriptions`: Scheduled command scanning expiring subscriptions and dispatching renewals notices.
5. **Controllers & Operations (`Tenant\NotificationController`)**:
   - `index`: Bilingual notifications inbox with unread/read filters, category badges, relative timestamps, and one-click actions.
   - `markAsRead`: Marks individual notification as read.
   - `markAllAsRead`: Marks all tenant notifications as read.
   - `destroy`: Safely removes notification.
   - `settings` & `updateSettings`: Notification channel toggles (in-app, email, SMS, WhatsApp) and threshold limits stored in tenant preferences.
   - `triggerAutomation`: Manual trigger for owner on-demand checks (low stock, customer credit, supplier payables).
6. **UI & Navigation**:
   - `resources/views/tenant/notifications/index.blade.php`: Rich notification inbox with color-coded severity pills, Urdu Nastaliq support, and quick mark-read actions.
   - `resources/views/tenant/notifications/settings.blade.php`: Automated alerts configuration form.
   - `resources/views/partials/header.blade.php`: Dynamic unread notification counter badge with pulse animation and drop-down preview.
   - `resources/views/partials/sidebar.blade.php`: Dedicated "Alerts & Automation" navigation item.
7. **Testing & Verification**:
   - `Phase17NotificationsAndAutomationTest`: 8 comprehensive test cases verifying low stock detection, daily sales summary calculations, daily wastage calculations, customer credit and supplier payable alerts, subscription expiry scanning, notification center UI, settings persistence, and multi-tenant notification isolation.
   - Complete application test suite: **137 tests passed, 773 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 18: Security, Performance & Backup (Completed 2026-11-15)
1. **Security Architecture & Enterprise Rate Limiting**:
   - `app/Providers/AppServiceProvider.php`: Registered named rate limiters (`login` at 5 attempts/min with IP+email throttle; `password-reset` at 5 attempts/min; `pos-transactions` at 60 checkout transactions/min; `backup-actions` at 10 operations/min; `api` at 60 requests/min).
   - Rate limiters wired to critical endpoints in `routes/web.php` (`/login`, `/forgot-password`, `/reset-password`, `/tenant/pos/checkout`, `/superadmin/backups/generate`, `/tenant/backup/export`).
2. **Services & Business Logic**:
   - `App\Services\SecurityAuditService`:
     - `auditTenantIsolation`: Deep audit scanning 25 tenant-owned tables to verify `tenant_id` presence, detect any orphaned tenant IDs, and confirm zero null tenant records (excluding platform super admins).
     - `sanitizeInput`: Input sanitization service neutralizing XSS injection payloads (`<script>`, inline handlers) while preserving international UTF-8 characters and Urdu Nastaliq glyphs.
     - `getSecurityMetrics`: Returns platform-wide security profile including CSRF posture, session HttpOnly cookies, Bcrypt password hashing, and isolation metrics.
   - `App\Services\BackupService`:
     - `createBackup`: Generates complete database dumps (supporting MySQL via WAMP and SQLite via pure PHP dump engine), optional Gzip compression (`.sql.gz`), SHA-256 tamper-evident checksum calculation, and manifest recording.
     - `exportTenantData`: Exports all isolated records for a single business tenant (produce catalog, stock, sales, purchases, customer receivables, supplier payables, and accounting ledgers) into structured JSON format with zero cross-tenant leakage.
     - `cleanOldBackups`: Automatically cleans outdated backup files according to a 30-day retention policy.
     - `listBackups`, `deleteBackup`, and `getBackupPath` with directory traversal protection.
3. **Artisan Console Automation Commands**:
   - `veggitable:backup`: Automated platform database snapshot command supporting `--tenant=`, `--no-compress`, and `--clean` retention options with CLI summary table.
   - `veggitable:clean-backups`: Command purging backup files older than specified days.
4. **Controllers & Operations**:
   - `SuperAdmin\BackupController`:
     - `index`: Disaster recovery command center with key metrics, backup archives table, multi-tenant isolation audit table, and security hardening specs.
     - `generate`: Triggers on-demand platform database backup with Gzip compression.
     - `download`: Streaming secure download with path traversal defense.
     - `destroy`: Permanently deletes backup archive.
   - `Tenant\BackupController`:
     - `index`: Tenant data export hub showing record counts across business modules.
     - `export`: Generates and downloads portable JSON business data export.
5. **Views & UI Integration**:
   - `resources/views/superadmin/backups/index.blade.php`: EasyAdmin Pro disaster recovery hub with metrics cards, backups list, isolation audit table, and security specs.
   - `resources/views/tenant/backup/index.blade.php`: Tenant business data export and download interface.
   - `resources/views/partials/sidebar.blade.php`: Added Super Admin "Backups & Security Audit" link and Tenant "Data Export & Backup" link.
6. **Testing & Verification**:
   - `Phase18SecurityPerformanceAndBackupTest`: 9 comprehensive test cases verifying login rate throttling, tenant isolation detection across 25 tables, XSS input sanitization preserving Nastaliq Urdu, full compressed database backup creation with SHA-256 checksums, tenant-specific isolated data export, 30-day backup retention purge, Artisan backup commands, Super Admin backup dashboard and secure downloads, and tenant owner data exports.
   - Complete application test suite: **146 tests passed, 936 assertions, 0 failures**.
   - Code formatted with Laravel Pint.

## Phase 19: Complete System Integration & Final Audit (Completed 2026-11-15)
1. **End-to-End Multi-Tenant Lifecycle Verification**:
   - **Scenario A (Business Onboarding & Setup)**: Verified complete lifecycle for a new business tenant from registration, profile & currency setup, branch outlet creation, cash/bank financial account creation, and default Chart of Accounts auto-seeding.
   - **Scenario B (Mandi Procurement & FIFO Stock)**: Executed wholesale procurement from mandi commission agent (`Haji Qasim`) with gross/tare/net scale weights, mandi commission fee (5%), market committee chungi, and freight carriage; verified immediate batch stock influx, supplier running payable balance update, and automated double-entry Journal Voucher posting (`Debits = Credits = Rs 13,200`).
   - **Scenario C (POS Scale Checkout & Cashier Till)**: Executed retail sale using decoded electronic produce scale EAN-13 barcodes (`2010045050003` -> PLU 10045, 5 kg) and cashier terminal with split cash and credit tender; verified FIFO stock depletion, customer ledger balance update, till session cash tracking, and session close reconciliation with zero cash difference.
   - **Scenario D (Produce Quality Regrading & Spoilage)**: Executed sorting and quality downgrade of 30 kg Grade A produce into 25 kg Grade B yield and 5 kg rotten cull spoilage; verified accurate stock adjustments and approved loss vouchers.
   - **Scenario E (Operational Expenses & Petty Cash)**: Executed operational freight expense disbursement through petty cash counter account, verifying instant account balance deduction and audit trail.
   - **Scenario F (Period-End Financial Statements & Z-Report)**: Verified Trial Balance debit/credit balancing, Day-End Register Z-Report cash reconciliation, and live P&L reporting.
   - **Scenario G (Bilingual Document Printing & Typography)**: Verified 80mm thermal receipt output in Noto Nastaliq Urdu, produce dual names, and spelled-out currency amounts in words (`numberToWords`).
   - **Scenario H (Multi-Tenant Isolation Enforcement)**: Confirmed two businesses operating simultaneously cannot access, view, print, or export each other's data (strictly enforced with 403 Forbidden / 404 Not Found responses).
2. **Testing & Verification**:
   - `Phase19CompleteSystemIntegrationTest`: 8 comprehensive end-to-end integration scenarios verifying the complete interconnected multi-tenant lifecycle.
   - Complete application test suite: **154 tests passed, 973 assertions, 0 failures**.
   - Code formatted with Laravel Pint.
