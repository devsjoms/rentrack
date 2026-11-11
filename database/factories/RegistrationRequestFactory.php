<?php

namespace Database\Factories;

use App\Models\RegistrationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistrationRequest> */
class RegistrationRequestFactory extends Factory
{
    protected $model = RegistrationRequest::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => 'Pending',
            'token' => null,
            'expires_at' => null,
            'approved_at' => null,
            'created_at' => now(),
        ];
    }
}
