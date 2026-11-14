<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class TenantOnboardingService
{
    /**
     * Seed default measurement units for vegetable and fruit business.
     */
    public function seedDefaultUnits(Tenant $tenant): void
    {
        $units = [
            ['name' => 'Kilogram', 'name_ur' => 'کلوگرام', 'code' => 'kg', 'is_fractional' => true, 'is_system' => true],
            ['name' => 'Gram', 'name_ur' => 'گرام', 'code' => 'g', 'is_fractional' => true, 'is_system' => true],
            ['name' => 'Sack / Bori', 'name_ur' => 'بوری / تھیلا', 'code' => 'bori', 'is_fractional' => false, 'is_system' => true],
            ['name' => 'Crate / Peti', 'name_ur' => 'کریٹ / پیٹی', 'code' => 'crate', 'is_fractional' => false, 'is_system' => true],
            ['name' => 'Dozen', 'name_ur' => 'درجن', 'code' => 'dz', 'is_fractional' => false, 'is_system' => true],
            ['name' => 'Piece / Dana', 'name_ur' => 'عدد / دانہ', 'code' => 'pc', 'is_fractional' => false, 'is_system' => true],
        ];

        foreach ($units as $u) {
            Unit::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $u['code']],
                array_merge($u, ['tenant_id' => $tenant->id])
            );
        }
    }

    /**
     * Seed default product and operational expense categories.
     */
    public function seedDefaultCategories(Tenant $tenant): void
    {
        $categories = [
            // Product Categories
            ['name' => 'Fresh Leafy Vegetables', 'name_ur' => 'پتوں والی سبزیاں', 'slug' => 'leafy-vegetables', 'type' => 'product', 'description' => 'Spinach, coriander, mint, fenugreek, mustard leaves.'],
            ['name' => 'Root & Tuber Vegetables', 'name_ur' => 'زمین دوز سبزیاں', 'slug' => 'root-vegetables', 'type' => 'product', 'description' => 'Potatoes, onions, garlic, ginger, carrots, radishes.'],
            ['name' => 'Fruiting & Gourd Vegetables', 'name_ur' => 'پھل دار سبزیاں', 'slug' => 'fruiting-vegetables', 'type' => 'product', 'description' => 'Tomatoes, okra, bitter gourd, eggplant, cucumber, bottle gourd.'],
            ['name' => 'Fresh Fruits', 'name_ur' => 'تازہ پھل', 'slug' => 'fresh-fruits', 'type' => 'product', 'description' => 'Apples, bananas, oranges, mangoes, seasonal fruits.'],
            ['name' => 'Mandi Wholesale Lots', 'name_ur' => 'منڈی ہول سیل لاٹس', 'slug' => 'mandi-lots', 'type' => 'product', 'description' => 'Unsorted bags and wooden crates received from Subzi Mandi auctions.'],

            // Expense Categories
            ['name' => 'Shop Rent', 'name_ur' => 'دکان کا کرایہ', 'slug' => 'shop-rent', 'type' => 'expense', 'description' => 'Monthly rental for retail shops and mandi stalls.'],
            ['name' => 'Electricity & Gas Bills', 'name_ur' => 'بجلی اور گیس بل', 'slug' => 'electricity-bills', 'type' => 'expense', 'description' => 'Power, refrigeration, and cold room cooling expenses.'],
            ['name' => 'Mandi Transport & Freight', 'name_ur' => 'منڈی کرایہ و باربرداری', 'slug' => 'mandi-freight', 'type' => 'expense', 'description' => 'Suzuki carriage, loader rickshaw, porter fees from auction yard.'],
            ['name' => 'Staff Salaries & Daily Wages', 'name_ur' => 'ملازمین کی تنخواہیں و دیہاڑی', 'slug' => 'staff-salaries', 'type' => 'expense', 'description' => 'Monthly salaries, weekly wages, and overtime.'],
            ['name' => 'Packaging Bags & Shoppers', 'name_ur' => 'شاپر اور پیکنگ بیگز', 'slug' => 'packaging-bags', 'type' => 'expense', 'description' => 'Plastic polybags, paper pouches, rubber bands, netting.'],
            ['name' => 'Daily Tea & Meals', 'name_ur' => 'روزانہ چائے کھانا', 'slug' => 'daily-tea-meals', 'type' => 'expense', 'description' => 'Counter tea, snacks, water, employee lunches.'],
            ['name' => 'Shop Maintenance & Cleaning', 'name_ur' => 'مرمت اور صفائی', 'slug' => 'shop-maintenance', 'type' => 'expense', 'description' => 'Weighing scale calibration, counter repair, cleaning.'],
            ['name' => 'Miscellaneous Overheads', 'name_ur' => 'متفرق اخراجات', 'slug' => 'miscellaneous-expenses', 'type' => 'expense', 'description' => 'Emergency expenses and petty cash.'],
        ];

        foreach ($categories as $c) {
            Category::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'type' => $c['type'], 'slug' => $c['slug']],
                array_merge($c, ['tenant_id' => $tenant->id])
            );
        }
    }

    /**
     * Create default Cash Drawer and Bank Account for tenant.
     */
    public function createDefaultAccounts(Tenant $tenant, ?Branch $branch = null, float $cashOpening = 0.0, float $bankOpening = 0.0): void
    {
        $branchId = $branch?->id ?? $tenant->headquarters?->id ?? $tenant->branches()->first()?->id;

        FinancialAccount::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Main Cash Counter Drawer'],
            [
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'type' => 'cash',
                'opening_balance' => $cashOpening,
                'current_balance' => $cashOpening,
                'is_active' => true,
            ]
        );

        FinancialAccount::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Primary Shop Bank Account'],
            [
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'type' => 'bank',
                'bank_name' => 'Habib Bank Limited (HBL)',
                'account_number' => '1234-56789012-03',
                'opening_balance' => $bankOpening,
                'current_balance' => $bankOpening,
                'is_active' => true,
            ]
        );
    }

    /**
     * Complete the onboarding wizard for a tenant.
     */
    public function completeOnboarding(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            // Ensure units, categories and accounts exist
            $this->seedDefaultUnits($tenant);
            $this->seedDefaultCategories($tenant);

            if ($tenant->accounts()->count() === 0) {
                $this->createDefaultAccounts($tenant);
            }

            $tenant->update([
                'onboarding_completed' => true,
                'onboarding_step' => 5,
            ]);

            AuditLog::record('tenant.onboarding_completed', [
                'tenant_name' => $tenant->name,
                'branches_count' => $tenant->branches()->count(),
                'accounts_count' => $tenant->accounts()->count(),
            ], auth()->user(), $tenant->id);
        });
    }
}
