<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CityFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'state' => fake()->state(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }
}
