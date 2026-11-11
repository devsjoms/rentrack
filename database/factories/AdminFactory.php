<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Admin> */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'admin_name' => fake()->name(),
            'phone_number' => fake()->numerify('09#########'),
            'last_login' => null,
            'status' => 'Active',
            'image' => null,
        ];
    }
}
