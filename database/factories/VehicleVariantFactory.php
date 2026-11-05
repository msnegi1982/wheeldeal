<?php

namespace Database\Factories;

use App\Enums\Transmission;
use App\Models\FuelType;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleVariantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['VX', 'ZX', 'Sigma', 'Alpha', 'SX', 'O', 'Signature']).' '.fake()->randomElement(['MT', 'AT', 'Turbo']);

        return [
            'vehicle_model_id' => VehicleModel::factory(),
            'fuel_type_id' => FuelType::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'transmission' => fake()->randomElement(Transmission::cases())->value,
            'engine_cc' => fake()->randomElement([1197, 1497, 1598, 1956, 2199]),
            'power_bhp' => fake()->randomFloat(2, 70, 200),
            'torque_nm' => fake()->numberBetween(110, 400),
            'mileage_kmpl' => fake()->randomFloat(2, 9, 28),
            'seats' => fake()->randomElement([5, 5, 7]),
            'price_min' => fake()->numberBetween(600000, 1500000),
            'price_max' => fake()->numberBetween(1600000, 3500000),
            'is_active' => true,
        ];
    }
}
