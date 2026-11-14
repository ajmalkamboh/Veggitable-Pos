<?php

use App\Core\MultiTenancy\TenantManager;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\ReportController as SuperAdminReportController;
use App\Http\Controllers\SuperAdmin\SubscriptionPaymentController as SuperAdminPaymentController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController as SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\TenantRecordController;
use App\Http\Controllers\TenantRegistrationController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

// Authentication entrypoint fallback (to be expanded in Phase 04)
Route::get('/login', function () {
    return redirect()->route('dashboard')->with('error', 'Please log in to continue.');
})->name('login');

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('language.switch');

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

// Tenant-Isolated Operations
Route::middleware(['tenant.active', 'tenant.prevent_cross'])->group(function () {
    Route::get('/tenant/records', [TenantRecordController::class, 'index'])->name('tenant.records.index');
    Route::post('/tenant/records', [TenantRecordController::class, 'store'])->name('tenant.records.store');
    Route::get('/tenant/records/{record}', [TenantRecordController::class, 'show'])->name('tenant.records.show');
    Route::post('/tenant/branch/{branch}/switch', [TenantRecordController::class, 'switchBranch'])->name('tenant.branch.switch');
});
