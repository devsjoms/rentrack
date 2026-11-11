<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'tenant_name' => fake()->name(),
            'tenant_age' => fake()->numberBetween(18, 70),
            'tenant_address' => fake()->address(),
            'tenant_number' => fake()->numerify('09#########'),
            'room_id' => null,
            'tenant_gender' => 'Male',
            'move_in_date' => null,
            'status' => 'Active',
            'image' => null,
        ];
    }
}
