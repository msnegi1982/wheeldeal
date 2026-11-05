<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'name' => fake()->name(),
            'phone' => '+91'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'message' => fake()->sentence(15),
            'status' => 'new',
            'source' => 'test',
        ];
    }
}
