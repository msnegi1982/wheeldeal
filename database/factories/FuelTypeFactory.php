<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FuelTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Petrol', 'Diesel', 'CNG', 'Electric', 'Hybrid'])
            .'-'.fake()->unique()->numberBetween(1, 999999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
