<?php

namespace App\Services;

use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationService
{
    /**
     * Register a new tenant with initial headquarters branch and owner user.
     *
     * @param  array{
     *     business_name: string,
     *     slug?: ?string,
     *     owner_name: string,
     *     owner_email: string,
     *     owner_password: string,
     *     phone?: ?string,
     *     city?: ?string,
     *     currency?: ?string,
     *     timezone?: ?string,
     *     default_locale?: ?string,
     *     settings?: ?array<string, mixed>
     * }  $data
     * @return array{tenant: Tenant, branch: Branch, owner: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $slug = ! empty($data['slug'])
                ? Str::slug($data['slug'])
                : Str::slug($data['business_name']);

            // Ensure slug uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$counter++;
            }

            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'status' => TenantStatus::Active,
                'email' => $data['owner_email'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'currency' => $data['currency'] ?? 'PKR',
                'timezone' => $data['timezone'] ?? 'Asia/Karachi',
                'default_locale' => $data['default_locale'] ?? 'en',
                'settings' => $data['settings'] ?? [
                    'fiscal_year_start' => '07-01',
                    'enable_thermal_receipt' => true,
                    'tax_rate' => 0.00,
                ],
                'trial_ends_at' => now()->addDays(14),
                'subscription_ends_at' => now()->addDays(14),
            ]);

            // 2. Create Initial Headquarters Branch
            $branch = Branch::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Headquarters Branch',
                'code' => 'HQ-01',
                'city' => $data['city'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['owner_email'],
                'is_headquarters' => true,
                'is_active' => true,
                'settings' => [],
            ]);

            // 3. Create Owner User
            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
                'is_super_admin' => false,
                'is_tenant_owner' => true,
                'status' => 'active',
                'phone' => $data['phone'] ?? null,
            ]);

            // 4. Attach Owner to Branch
            $owner->branches()->syncWithoutDetaching([
                $branch->id => [
                    'tenant_id' => $tenant->id,
                    'is_default' => true,
                ],
            ]);

            return [
                'tenant' => $tenant,
                'branch' => $branch,
                'owner' => $owner,
            ];
        });
    }
}
