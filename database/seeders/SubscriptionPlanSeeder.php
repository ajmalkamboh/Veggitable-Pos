<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed standard subscription plans.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'description' => 'Ideal for single-shop vegetable retail stores starting computerized billing.',
                'price_monthly' => 1500.00,
                'price_yearly' => 15000.00,
                'trial_days' => 14,
                'grace_days' => 3,
                'max_branches' => 1,
                'max_users' => 2,
                'max_products' => 100,
                'features' => [
                    'pos_counter',
                    'purchases',
                    'inventory_management',
                    'urdu_receipts',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard Plan',
                'slug' => 'standard',
                'description' => 'Best value for growing grocery shops with multiple counters and wastage tracking.',
                'price_monthly' => 3500.00,
                'price_yearly' => 35000.00,
                'trial_days' => 14,
                'grace_days' => 3,
                'max_branches' => 3,
                'max_users' => 5,
                'max_products' => 500,
                'features' => [
                    'pos_counter',
                    'purchases',
                    'inventory_management',
                    'urdu_receipts',
                    'wastage_tracking',
                    'expense_management',
                    'custom_reports',
                ],
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional Plan',
                'slug' => 'professional',
                'description' => 'Complete ERP suite for multi-branch vegetable chains with double-entry accounting.',
                'price_monthly' => 6500.00,
                'price_yearly' => 65000.00,
                'trial_days' => 14,
                'grace_days' => 5,
                'max_branches' => 10,
                'max_users' => 15,
                'max_products' => 2000,
                'features' => [
                    'pos_counter',
                    'purchases',
                    'inventory_management',
                    'urdu_receipts',
                    'wastage_tracking',
                    'expense_management',
                    'custom_reports',
                    'accounting_ledger',
                    'multi_warehouse',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'description' => 'Unlimited capability with direct API integrations, audit trails, and priority support.',
                'price_monthly' => 12000.00,
                'price_yearly' => 120000.00,
                'trial_days' => 30,
                'grace_days' => 7,
                'max_branches' => -1,
                'max_users' => -1,
                'max_products' => -1,
                'features' => [
                    'pos_counter',
                    'purchases',
                    'inventory_management',
                    'urdu_receipts',
                    'wastage_tracking',
                    'expense_management',
                    'custom_reports',
                    'accounting_ledger',
                    'multi_warehouse',
                    'api_access',
                    'audit_trails',
                    'priority_support',
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
