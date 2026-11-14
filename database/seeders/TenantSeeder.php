<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantRecord;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TenantOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptionService = app(SubscriptionService::class);
        $onboardingService = app(TenantOnboardingService::class);
        $standardPlan = SubscriptionPlan::where('slug', 'standard')->first();
        $basicPlan = SubscriptionPlan::where('slug', 'basic')->first();

        $ownerRole = Role::where('slug', 'owner')->first();
        $cashierRole = Role::where('slug', 'cashier')->first();

        // 1. Platform Super Admin (No tenant attachment)
        User::firstOrCreate(
            ['email' => 'admin@veggitable.test'],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'is_tenant_owner' => false,
                'status' => 'active',
            ]
        );

        // 2. Tenant A: Al-Madina Vegetables
        $tenantA = Tenant::firstOrCreate(
            ['slug' => 'al-madina'],
            [
                'name' => 'Al-Madina Fresh Vegetables',
                'status' => TenantStatus::Active,
                'email' => 'info@almadina.test',
                'phone' => '+92 300 1234567',
                'city' => 'Lahore',
                'currency' => 'PKR',
                'timezone' => 'Asia/Karachi',
                'default_locale' => 'ur',
                'trial_ends_at' => now()->addDays(30),
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        if ($standardPlan && ! $tenantA->plan_id) {
            $subscriptionService->assignPlan(
                tenant: $tenantA,
                plan: $standardPlan,
                cycle: BillingCycle::Monthly,
                amountPaid: 3500.00,
                paymentMethod: 'bank_transfer',
                reference: 'INIT-ALMADINA-01',
                notes: 'Initial standard plan subscription for Al-Madina Fresh Vegetables.'
            );
            $tenantA->refresh();
        }

        $branchA1 = Branch::firstOrCreate(
            ['tenant_id' => $tenantA->id, 'code' => 'HQ-01'],
            [
                'name' => 'Main Market Head Branch',
                'city' => 'Lahore',
                'is_headquarters' => true,
                'is_active' => true,
            ]
        );

        $branchA2 = Branch::firstOrCreate(
            ['tenant_id' => $tenantA->id, 'code' => 'BR-02'],
            [
                'name' => 'Subzi Mandi Branch',
                'city' => 'Lahore',
                'is_headquarters' => false,
                'is_active' => true,
            ]
        );

        $ownerA = User::firstOrCreate(
            ['email' => 'owner@almadina.test'],
            [
                'name' => 'Haji Muhammad (Owner)',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantA->id,
                'current_branch_id' => $branchA1->id,
                'is_super_admin' => false,
                'is_tenant_owner' => true,
                'status' => 'active',
            ]
        );

        $ownerA->branches()->syncWithoutDetaching([
            $branchA1->id => ['tenant_id' => $tenantA->id, 'is_default' => true],
            $branchA2->id => ['tenant_id' => $tenantA->id, 'is_default' => false],
        ]);

        if ($ownerRole) {
            $ownerA->roles()->syncWithoutDetaching([
                $ownerRole->id => ['tenant_id' => $tenantA->id],
            ]);
        }

        // Add Cashier employee to Tenant A
        $cashierA = User::firstOrCreate(
            ['email' => 'cashier@almadina.test'],
            [
                'name' => 'Bilal Cashier',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantA->id,
                'current_branch_id' => $branchA1->id,
                'is_super_admin' => false,
                'is_tenant_owner' => false,
                'status' => 'active',
            ]
        );

        $cashierA->branches()->syncWithoutDetaching([
            $branchA1->id => ['tenant_id' => $tenantA->id, 'is_default' => true],
        ]);

        if ($cashierRole) {
            $cashierA->roles()->syncWithoutDetaching([
                $cashierRole->id => ['tenant_id' => $tenantA->id],
            ]);
        }

        // 3. Tenant B: Bismillah Green Grocers
        $tenantB = Tenant::firstOrCreate(
            ['slug' => 'bismillah'],
            [
                'name' => 'Bismillah Green Grocers',
                'status' => TenantStatus::Active,
                'email' => 'info@bismillah.test',
                'phone' => '+92 321 7654321',
                'city' => 'Karachi',
                'currency' => 'PKR',
                'timezone' => 'Asia/Karachi',
                'default_locale' => 'en',
                'trial_ends_at' => now()->addDays(30),
                'subscription_ends_at' => now()->addYear(),
            ]
        );

        if ($basicPlan && ! $tenantB->plan_id) {
            $subscriptionService->assignPlan(
                tenant: $tenantB,
                plan: $basicPlan,
                cycle: BillingCycle::Monthly,
                amountPaid: 1500.00,
                paymentMethod: 'cash',
                reference: 'INIT-BISMILLAH-01',
                notes: 'Initial basic plan subscription for Bismillah Green Grocers.'
            );
            $tenantB->refresh();
        }

        $branchB1 = Branch::firstOrCreate(
            ['tenant_id' => $tenantB->id, 'code' => 'HQ-01'], // Note: Same code HQ-01 as Tenant A!
            [
                'name' => 'Bismillah Central Branch',
                'city' => 'Karachi',
                'is_headquarters' => true,
                'is_active' => true,
            ]
        );

        $ownerB = User::firstOrCreate(
            ['email' => 'owner@bismillah.test'],
            [
                'name' => 'Abdul Rasheed (Owner)',
                'password' => Hash::make('password'),
                'tenant_id' => $tenantB->id,
                'current_branch_id' => $branchB1->id,
                'is_super_admin' => false,
                'is_tenant_owner' => true,
                'status' => 'active',
            ]
        );

        $ownerB->branches()->syncWithoutDetaching([
            $branchB1->id => ['tenant_id' => $tenantB->id, 'is_default' => true],
        ]);

        if ($ownerRole) {
            $ownerB->roles()->syncWithoutDetaching([
                $ownerRole->id => ['tenant_id' => $tenantB->id],
            ]);
        }

        // Complete onboarding setup for demo tenants
        $onboardingService->completeOnboarding($tenantA);
        $onboardingService->completeOnboarding($tenantB);

        // Seed Sample Isolated Records
        TenantRecord::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantA->id, 'code' => 'REC-001'],
            [
                'branch_id' => $branchA1->id,
                'name' => 'Fresh Farm Tomatoes (Al-Madina)',
                'amount' => 12500.00,
                'description' => 'Tenant A private inventory lot',
            ]
        );

        TenantRecord::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenantB->id, 'code' => 'REC-001'], // Note: Same code REC-001 as Tenant A!
            [
                'branch_id' => $branchB1->id,
                'name' => 'Imported Onions (Bismillah)',
                'amount' => 28000.00,
                'description' => 'Tenant B private inventory lot',
            ]
        );
    }
}
