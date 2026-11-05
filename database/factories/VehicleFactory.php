<?php

namespace Database\Factories;

use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\FuelType;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $fuel = FuelType::factory()->create();
        $model = VehicleModel::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        $variant = VehicleVariant::factory()->create([
            'vehicle_model_id' => $model->id,
            'fuel_type_id' => $fuel->id,
        ]);
        $year = fake()->numberBetween(2017, 2025);
        $title = $year.' '.$brand->name.' '.$model->name.' '.$variant->name;

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 999999),
            'brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'vehicle_variant_id' => $variant->id,
            'category_id' => $category->id,
            'fuel_type_id' => $fuel->id,
            'city_id' => City::factory(),
            'year' => $year,
            'price' => fake()->numberBetween(350000, 4500000),
            'original_price' => fake()->optional()->numberBetween(500000, 5000000),
            'km_driven' => fake()->numberBetween(5000, 120000),
            'owner_count' => fake()->numberBetween(1, 3),
            'transmission' => fake()->randomElement(Transmission::cases())->value,
            'seats' => fake()->randomElement([5, 5, 7]),
            'colour' => fake()->randomElement(['Pearl White', 'Midnight Black', 'Titanium Grey', 'Fiery Red', 'Ocean Blue']),
            'registration_state' => fake()->state(),
            'registration_year' => $year,
            'engine_cc' => fake()->randomElement([1197, 1497, 1598, 1956, 2199]),
            'power_bhp' => fake()->randomFloat(2, 70, 200),
            'torque_nm' => fake()->numberBetween(110, 400),
            'mileage_kmpl' => fake()->randomFloat(2, 9, 28),
            'description' => fake()->paragraphs(3, true),
            'condition' => fake()->randomElement(VehicleCondition::cases())->value,
            'status' => VehicleStatus::PUBLISHED->value,
            'is_featured' => fake()->boolean(35),
            'is_popular' => fake()->boolean(35),
            'popularity_score' => fake()->numberBetween(0, 1000),
            'views_count' => fake()->numberBetween(0, 5000),
            'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::DRAFT->value, 'published_at' => null]);
    }

    public function sold(): static
    {
        return $this->state(fn () => ['status' => VehicleStatus::SOLD->value, 'sold_at' => now()]);
    }
}
