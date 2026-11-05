<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 9999),
            'short_description' => fake()->sentence(10),
            'description' => fake()->paragraphs(3, true),
            'meta_title' => $name.' cars — prices, specs and used listings',
            'meta_description' => 'Explore new and used '.$name.' cars with specs, photos and verified sellers.',
            'is_featured' => fake()->boolean(40),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
