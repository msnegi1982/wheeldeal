<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FeatureFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'category' => fake()->randomElement(['safety', 'comfort', 'technology', 'exterior']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }
}
