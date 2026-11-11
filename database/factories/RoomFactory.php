<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => fake()->unique()->bothify('R-##?'),
            'building_number' => fake()->bothify('B-#'),
            'building_address' => fake()->streetAddress(),
            'gender_allowed' => 'Male',
            'tenants_count' => 0,
            'maximum_tenants' => 2,
            'rent_price' => '5000.00',
            'availability_status' => 'Available',
        ];
    }
}
