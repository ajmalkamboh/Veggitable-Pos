<?php

use App\Core\MultiTenancy\TenantManager;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\TenantRecordController;
use App\Http\Controllers\TenantRegistrationController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('language.switch');

// Tenant Onboarding & Registration
Route::get('/register-business', [TenantRegistrationController::class, 'create'])->name('tenant.register.create');
Route::post('/register-business', [TenantRegistrationController::class, 'store'])->name('tenant.register.store');

// Tenant Context Switcher (for development and multi-tenant testing)
Route::get('/tenant/switch/{tenant}', function (Tenant $tenant, TenantManager $manager) {
    $manager->activateTenant($tenant);

    return redirect()->back()->with('success', "Active tenant switched to '{$tenant->name}'.");
})->name('tenant.switch');

// Tenant-Isolated Operations
Route::middleware(['tenant.active', 'tenant.prevent_cross'])->group(function () {
    Route::get('/tenant/records', [TenantRecordController::class, 'index'])->name('tenant.records.index');
    Route::post('/tenant/records', [TenantRecordController::class, 'store'])->name('tenant.records.store');
    Route::get('/tenant/records/{record}', [TenantRecordController::class, 'show'])->name('tenant.records.show');
    Route::post('/tenant/branch/{branch}/switch', [TenantRecordController::class, 'switchBranch'])->name('tenant.branch.switch');
});
