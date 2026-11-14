<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantRecord>
 */
class TenantRecordFactory extends Factory
{
    protected $model = TenantRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => null,
            'name' => fake()->words(2, true),
            'code' => 'REC-'.fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'metadata' => ['category' => 'Vegetables'],
        ];
    }
}
