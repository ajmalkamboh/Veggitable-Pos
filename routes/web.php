<?php

use App\Core\MultiTenancy\TenantManager;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\ReportController as SuperAdminReportController;
use App\Http\Controllers\SuperAdmin\SubscriptionPaymentController as SuperAdminPaymentController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController as SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\Tenant\AuditLogController as TenantAuditController;
use App\Http\Controllers\Tenant\CategoryController as TenantCategoryController;
use App\Http\Controllers\Tenant\CustomerController as TenantCustomerController;
use App\Http\Controllers\Tenant\InventoryController as TenantInventoryController;
use App\Http\Controllers\Tenant\OnboardingController as TenantOnboardingController;
use App\Http\Controllers\Tenant\PosController as TenantPosController;
use App\Http\Controllers\Tenant\PosSessionController as TenantPosSessionController;
use App\Http\Controllers\Tenant\ProductController as TenantProductController;
use App\Http\Controllers\Tenant\PurchaseInvoiceController as TenantPurchaseInvoiceController;
use App\Http\Controllers\Tenant\PurchaseReturnController as TenantPurchaseReturnController;
use App\Http\Controllers\Tenant\RoleController as TenantRoleController;
use App\Http\Controllers\Tenant\SalesInvoiceController as TenantSalesInvoiceController;
use App\Http\Controllers\Tenant\StockAdjustmentController as TenantStockAdjustmentController;
use App\Http\Controllers\Tenant\StockTransferController as TenantStockTransferController;
use App\Http\Controllers\Tenant\SupplierController as TenantSupplierController;
use App\Http\Controllers\Tenant\UnitController as TenantUnitController;
use App\Http\Controllers\Tenant\UserController as TenantUserController;
use App\Http\Controllers\TenantRecordController;
use App\Http\Controllers\TenantRegistrationController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

// Language Switcher
Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('language.switch');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'updatePassword'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

// Tenant Onboarding & Registration
Route::get('/register-business', [TenantRegistrationController::class, 'create'])->name('tenant.register.create');
Route::post('/register-business', [TenantRegistrationController::class, 'store'])->name('tenant.register.store');

// Tenant Context Switcher (for development and multi-tenant testing)
Route::get('/tenant/switch/{tenant}', function (Tenant $tenant, TenantManager $manager) {
    $manager->activateTenant($tenant);

    return redirect()->back()->with('success', "Active tenant switched to '{$tenant->name}'.");
})->name('tenant.switch');

// Super Admin Platform & Subscription Management
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

    // Tenant Management
    Route::get('/tenants', [SuperAdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [SuperAdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [SuperAdminTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [SuperAdminTenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/toggle-status', [SuperAdminTenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
    Route::post('/tenants/{tenant}/change-plan', [SuperAdminTenantController::class, 'changePlan'])->name('tenants.change-plan');
    Route::post('/tenants/{tenant}/extend', [SuperAdminTenantController::class, 'extendSubscription'])->name('tenants.extend');

    // Subscription Plans CRUD
    Route::resource('plans', SuperAdminPlanController::class);

    // Subscription Payments & Invoices
    Route::get('/payments', [SuperAdminPaymentController::class, 'index'])->name('subscriptions.payments');
    Route::get('/payments/create', [SuperAdminPaymentController::class, 'create'])->name('subscriptions.create');
    Route::post('/payments', [SuperAdminPaymentController::class, 'store'])->name('subscriptions.store');
    Route::get('/payments/{invoice}', [SuperAdminPaymentController::class, 'show'])->name('subscriptions.show');

    // Platform Reports
    Route::get('/reports', [SuperAdminReportController::class, 'index'])->name('reports.index');
});

// Tenant Operations & Administration
Route::middleware(['auth', 'tenant.active', 'tenant.prevent_cross'])->group(function () {
    // Business Onboarding Wizard
    Route::prefix('tenant/onboarding')->name('tenant.onboarding.')->group(function () {
        Route::get('/', [TenantOnboardingController::class, 'index'])->name('index');
        Route::get('/step1', [TenantOnboardingController::class, 'step1'])->name('step1');
        Route::post('/step1', [TenantOnboardingController::class, 'updateProfile'])->name('step1.update');
        Route::get('/step2', [TenantOnboardingController::class, 'step2'])->name('step2');
        Route::post('/step2', [TenantOnboardingController::class, 'saveBranch'])->name('step2.save');
        Route::get('/step3', [TenantOnboardingController::class, 'step3'])->name('step3');
        Route::post('/step3', [TenantOnboardingController::class, 'saveAccounts'])->name('step3.save');
        Route::get('/step4', [TenantOnboardingController::class, 'step4'])->name('step4');
        Route::post('/finish', [TenantOnboardingController::class, 'finish'])->name('finish');
    });

    // Staff & User Management (Protected by 'users.manage' or Owner)
    Route::prefix('tenant/users')->name('tenant.users.')->group(function () {
        Route::get('/', [TenantUserController::class, 'index'])->name('index');
        Route::get('/create', [TenantUserController::class, 'create'])->name('create');
        Route::post('/', [TenantUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [TenantUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [TenantUserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [TenantUserController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Roles & Permissions Matrix
    Route::prefix('tenant/roles')->name('tenant.roles.')->group(function () {
        Route::get('/', [TenantRoleController::class, 'index'])->name('index');
        Route::get('/create', [TenantRoleController::class, 'create'])->name('create');
        Route::post('/', [TenantRoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [TenantRoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [TenantRoleController::class, 'update'])->name('update');
    });

    // Security & Audit Trail
    Route::get('/tenant/audit', [TenantAuditController::class, 'index'])->name('tenant.audit.index');

    // Branch Context Switcher
    Route::post('/tenant/branch/{branch}/switch', [TenantRecordController::class, 'switchBranch'])->name('tenant.branch.switch');

    // Operational Business Routes (Gated by mandatory onboarding completion)
    Route::middleware('tenant.onboarded')->group(function () {
        Route::get('/tenant/records', [TenantRecordController::class, 'index'])->name('tenant.records.index');
        Route::post('/tenant/records', [TenantRecordController::class, 'store'])->name('tenant.records.store');
        Route::get('/tenant/records/{record}', [TenantRecordController::class, 'show'])->name('tenant.records.show');

        // Master Categories & Subcategories
        Route::prefix('tenant/categories')->name('tenant.categories.')->group(function () {
            Route::get('/', [TenantCategoryController::class, 'index'])->name('index');
            Route::get('/create', [TenantCategoryController::class, 'create'])->name('create');
            Route::post('/', [TenantCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [TenantCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [TenantCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [TenantCategoryController::class, 'destroy'])->name('destroy');
        });

        // Measurement Units & Conversions
        Route::prefix('tenant/units')->name('tenant.units.')->group(function () {
            Route::get('/', [TenantUnitController::class, 'index'])->name('index');
            Route::get('/create', [TenantUnitController::class, 'create'])->name('create');
            Route::post('/', [TenantUnitController::class, 'store'])->name('store');
            Route::get('/{unit}/edit', [TenantUnitController::class, 'edit'])->name('edit');
            Route::put('/{unit}', [TenantUnitController::class, 'update'])->name('update');
            Route::delete('/{unit}', [TenantUnitController::class, 'destroy'])->name('destroy');
            Route::post('/conversions', [TenantUnitController::class, 'storeConversion'])->name('conversions.store');
            Route::delete('/conversions/{conversion}', [TenantUnitController::class, 'destroyConversion'])->name('conversions.destroy');
        });

        // Products Catalog & Barcodes
        Route::prefix('tenant/products')->name('tenant.products.')->group(function () {
            Route::get('/', [TenantProductController::class, 'index'])->name('index');
            Route::get('/create', [TenantProductController::class, 'create'])->name('create');
            Route::post('/', [TenantProductController::class, 'store'])->name('store');
            Route::get('/{product}', [TenantProductController::class, 'show'])->name('show');
            Route::get('/{product}/edit', [TenantProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [TenantProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [TenantProductController::class, 'destroy'])->name('destroy');
            Route::get('/{product}/barcode', [TenantProductController::class, 'barcode'])->name('barcode');
        });

        // Suppliers (Commission Agents & Farmers)
        Route::prefix('tenant/suppliers')->name('tenant.suppliers.')->group(function () {
            Route::get('/', [TenantSupplierController::class, 'index'])->name('index');
            Route::get('/create', [TenantSupplierController::class, 'create'])->name('create');
            Route::post('/', [TenantSupplierController::class, 'store'])->name('store');
            Route::get('/{supplier}', [TenantSupplierController::class, 'show'])->name('show');
            Route::get('/{supplier}/edit', [TenantSupplierController::class, 'edit'])->name('edit');
            Route::put('/{supplier}', [TenantSupplierController::class, 'update'])->name('update');
            Route::delete('/{supplier}', [TenantSupplierController::class, 'destroy'])->name('destroy');
            Route::post('/{supplier}/payment', [TenantSupplierController::class, 'recordPayment'])->name('payment');
        });

        // Customers (Retail Walk-in & Commercial Accounts)
        Route::prefix('tenant/customers')->name('tenant.customers.')->group(function () {
            Route::get('/', [TenantCustomerController::class, 'index'])->name('index');
            Route::get('/create', [TenantCustomerController::class, 'create'])->name('create');
            Route::post('/', [TenantCustomerController::class, 'store'])->name('store');
            Route::get('/{customer}', [TenantCustomerController::class, 'show'])->name('show');
            Route::get('/{customer}/edit', [TenantCustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer}', [TenantCustomerController::class, 'update'])->name('update');
            Route::delete('/{customer}', [TenantCustomerController::class, 'destroy'])->name('destroy');
            Route::post('/{customer}/receipt', [TenantCustomerController::class, 'recordReceipt'])->name('receipt');
        });

        // Mandi Purchases & Procurement
        Route::prefix('tenant/purchases')->name('tenant.purchases.')->group(function () {
            Route::get('/', [TenantPurchaseInvoiceController::class, 'index'])->name('index');
            Route::get('/create', [TenantPurchaseInvoiceController::class, 'create'])->name('create');
            Route::post('/', [TenantPurchaseInvoiceController::class, 'store'])->name('store');
            Route::get('/{purchaseInvoice}', [TenantPurchaseInvoiceController::class, 'show'])->name('show');
            Route::get('/{purchaseInvoice}/print', [TenantPurchaseInvoiceController::class, 'print'])->name('print');
            Route::delete('/{purchaseInvoice}', [TenantPurchaseInvoiceController::class, 'destroy'])->name('destroy');
            Route::post('/{purchaseInvoice}/returns', [TenantPurchaseReturnController::class, 'store'])->name('returns.store');
        });

        // Inventory, Produce Batches & Stock Movements
        Route::prefix('tenant/inventory')->name('tenant.inventory.')->group(function () {
            Route::get('/', [TenantInventoryController::class, 'index'])->name('index');
            Route::get('/batches', [TenantInventoryController::class, 'batches'])->name('batches');
            Route::get('/movements', [TenantInventoryController::class, 'movements'])->name('movements');

            // Stock Transfers (Branch-to-Branch)
            Route::prefix('transfers')->name('transfers.')->group(function () {
                Route::get('/', [TenantStockTransferController::class, 'index'])->name('index');
                Route::get('/create', [TenantStockTransferController::class, 'create'])->name('create');
                Route::post('/', [TenantStockTransferController::class, 'store'])->name('store');
                Route::get('/{stockTransfer}', [TenantStockTransferController::class, 'show'])->name('show');
                Route::post('/{stockTransfer}/receive', [TenantStockTransferController::class, 'receive'])->name('receive');
            });

            // Stock Physical Audit & Discrepancy Adjustments
            Route::prefix('adjustments')->name('adjustments.')->group(function () {
                Route::get('/', [TenantStockAdjustmentController::class, 'index'])->name('index');
                Route::get('/create', [TenantStockAdjustmentController::class, 'create'])->name('create');
                Route::post('/', [TenantStockAdjustmentController::class, 'store'])->name('store');
                Route::get('/{stockAdjustment}', [TenantStockAdjustmentController::class, 'show'])->name('show');
            });
        });

        // POS Counter Terminal & Fast Billing
        Route::prefix('tenant/pos')->name('tenant.pos.')->group(function () {
            Route::get('/', [TenantPosController::class, 'counter'])->name('counter');
            Route::post('/checkout', [TenantPosController::class, 'checkout'])->name('checkout');
            Route::post('/hold', [TenantPosController::class, 'hold'])->name('hold');
            Route::get('/held-tickets', [TenantPosController::class, 'heldTickets'])->name('held-tickets');
            Route::get('/receipt/{salesInvoice}', [TenantPosController::class, 'receipt'])->name('receipt');

            // Till Sessions
            Route::post('/session/open', [TenantPosSessionController::class, 'open'])->name('session.open');
            Route::post('/session/{posSession}/close', [TenantPosSessionController::class, 'close'])->name('session.close');
        });

        // Sales Invoices & Orders History
        Route::prefix('tenant/sales')->name('tenant.sales.')->group(function () {
            Route::get('/', [TenantSalesInvoiceController::class, 'index'])->name('index');
            Route::get('/{salesInvoice}', [TenantSalesInvoiceController::class, 'show'])->name('show');
            Route::post('/{salesInvoice}/returns', [TenantSalesInvoiceController::class, 'returnItem'])->name('returns.store');
        });
    });
});
