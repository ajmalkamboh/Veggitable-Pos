<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Vegetable Shop';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => TenantStatus::Active,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'default_locale' => 'en',
            'settings' => [
                'fiscal_year_start' => '07-01',
                'enable_thermal_receipt' => true,
            ],
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => now()->addYear(),
        ];
    }

    /**
     * State for a suspended tenant.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Suspended,
        ]);
    }

    /**
     * State for an expired tenant.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Expired,
            'subscription_ends_at' => now()->subDay(),
        ]);
    }
}
