# VEGGITABLE SAAS - SYSTEM ARCHITECTURE DOCUMENTATION

## 1. High-Level Architecture Overview

Veggitable SaaS is an international-standard, multi-tenant Software-as-a-Service (SaaS) management platform designed specifically for vegetable and fruit wholesale/retail businesses.

### Core Architecture Principles:
1. **Multi-Tenancy with Shared Database Architecture**:
   - Single MySQL database with strict row-level tenant isolation enforced by `tenant_id`.
   - Global Eloquent scopes + Service Layer authorization + Form Request/Policy checks prevent cross-tenant leakage.
   - Platform administration (Super Admin) is cleanly segregated from tenant shop operations.
2. **Double-Entry Accounting Subsystem**:
   - Every financial transaction posts balanced debits and credits (`Debit == Credit`).
   - Implements permanent auditability: posted journal entries cannot be deleted, only adjusted via reversing vouchers.
   - Distinct separation between Cash Accounts, Bank Accounts, Accounts Receivable, Accounts Payable, Inventory Assets, Sales, Cost of Goods Sold (COGS), Wastage/Spoilage, and Operating Expenses.
3. **Perishable Vegetable Costing & Inventory Engine**:
   - FIFO (First-In, First-Out) costing engine supporting perishable batches.
   - Dual-unit tracking: crate/bag to kilogram (KG) conversions with product-specific yield factors.
   - Spoilage, weight loss, and rotten vegetable wastage are accounted for at FIFO cost.
4. **Bilingual Accessibility (Urdu & English)**:
   - Full bidirectional layout support: LTR for English and RTL for Urdu.
   - Nastaliq typography (`Noto Nastaliq Urdu` & `Noto Sans Arabic`) with standard English technical identifiers.
   - Session-persisted locale and user-level preference overrides.

---

## 2. Technology Stack & Runtime Configuration

- **Framework**: Laravel 13 / 12 Core
- **Language & Runtime**: PHP 8.3+ (Strict types, Constructor Promotion, Enums, Typed Returns)
- **Frontend Reactive Layer**: Livewire 4 / 3 Reactive Components (Single file & Class-based)
- **CSS Framework & Admin UI**: Bootstrap 5.3 + EasyAdmin Pro Template + Custom RTL Engine
- **Relational Database**: MySQL 8.4+ (utf8mb4_unicode_ci)
- **Reporting Engines**:
  - PDF Generation: `barryvdh/laravel-dompdf` (DomPDF 3.1) for 80mm thermal receipts and A4 invoices.
  - Excel Export/Import: `maatwebsite/excel` (PhpSpreadsheet 5.11) for ledgers and financial statements.
- **Development Environment**: Local WAMP on Windows (`C:\wamp64\www\Veggitable`).

---

## 3. Platform Hierarchy & Boundaries

```
[ Super Admin SaaS Platform ]
  │
  ├── Tenant / Business (e.g. Al-Madina Fresh Veggies)
  │     ├── Subscription & Feature Entitlements
  │     ├── Branches (e.g. Main Market, Sub-Market)
  │     │     ├── Warehouses / Cold Stores
  │     │     ├── POS Counters & Weighing Scales
  │     │     └── Branch Cash Drawers
  │     ├── Users (Owner, Manager, Cashier, Accountant)
  │     ├── Products & Perishable Batches
  │     ├── Double-Entry Chart of Accounts
  │     └── Customers & Suppliers Ledgers
  │
  └── Tenant / Business B (Completely Isolated)
```

---

## 4. Security & Isolation Matrix

| Layer | Enforcement Mechanism | Failure Defense |
|---|---|---|
| Route & Middleware | `TenantMiddleware`, `CheckTenantStatus` | Rejects requests without active, valid tenant |
| Model & ORM | `TenantScope` auto-applied to all Tenant-owned models | Excludes cross-tenant queries at SQL generation |
| Service & Business Logic | Explicit `tenant_id` verification against active context | Disallows spoofed client parameters |
| Database Indexing | Compound unique keys: `UNIQUE(tenant_id, code)` | Prevents cross-tenant identifier collision |

