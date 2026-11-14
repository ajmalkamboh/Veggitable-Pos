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
use App\Http\Controllers\Tenant\RoleController as TenantRoleController;
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

// Tenant-Isolated Operations
Route::middleware(['auth', 'tenant.active', 'tenant.prevent_cross'])->group(function () {
    // Isolated Records Demo
    Route::get('/tenant/records', [TenantRecordController::class, 'index'])->name('tenant.records.index');
    Route::post('/tenant/records', [TenantRecordController::class, 'store'])->name('tenant.records.store');
    Route::get('/tenant/records/{record}', [TenantRecordController::class, 'show'])->name('tenant.records.show');
    Route::post('/tenant/branch/{branch}/switch', [TenantRecordController::class, 'switchBranch'])->name('tenant.branch.switch');

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
});
