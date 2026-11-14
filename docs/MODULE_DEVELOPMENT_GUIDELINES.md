# MODULE DEVELOPMENT GUIDELINES

For every phase and feature module in the Veggitable SaaS system, adhere to the standard module lifecycle:

### Step 1: Database Migration & Schema Design
- Create migration using `php artisan make:migration create_<table_name>_table`.
- Include `tenant_id` foreign key index on all tenant-owned models.
- Set appropriate decimal precision for weights (`12, 3`) and currency (`15, 2`).
- Include composite unique indexes: `['tenant_id', 'code']`.

### Step 2: Eloquent Model & Scopes
- Create model using `php artisan make:model <ModelName>`.
- Use `BelongsToTenant` trait to automatically attach `tenant_id` and apply global tenant scopes.
- Define cast attributes (`decimal:2`, `decimal:3`, Enums, DateTime).
- Define relationships explicitly with full return types (`BelongsTo`, `HasMany`).

### Step 3: Domain Service & Transactions
- Encapsulate business rules in `App\Services\<ModuleName>Service`.
- Wrap modifications in `DB::transaction(...)`.
- Coordinate inventory stock movements via `StockService` and accounting journal vouchers via `JournalService`.

### Step 4: Livewire / Blade Presentation
- Build reactive components reusing the Bootstrap 5 EasyAdmin Pro UI controls.
- Include English (`lang/en/<module>.php`) and Urdu (`lang/ur/<module>.php`) language keys.
- Ensure buttons display loading states and inputs display inline validation errors.

### Step 5: Automated Testing
- Create PHPUnit Feature test using `php artisan make:test --phpunit <ModuleName>Test`.
- Test positive path, validation failures, tenant data isolation (Tenant A cannot see Tenant B), and translation rendering.

