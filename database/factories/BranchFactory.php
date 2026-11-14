<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->streetName().' Branch',
            'code' => 'BR-'.fake()->unique()->numberBetween(10, 999),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_headquarters' => false,
            'is_active' => true,
            'settings' => [],
        ];
    }

    /**
     * State for a headquarters branch.
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Headquarters Branch',
            'code' => 'HQ-01',
            'is_headquarters' => true,
        ]);
    }
}
