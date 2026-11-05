<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(RequestType::cases())->value,
            'status' => RequestStatus::NEW->value,
            'name' => fake()->name(),
            'phone' => '+91'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'city_id' => City::factory(),
            'year' => fake()->numberBetween(2015, 2025),
            'km_driven' => fake()->numberBetween(1000, 100000),
            'expected_price' => fake()->numberBetween(300000, 3000000),
            'condition' => 'good',
            'colour' => fake()->safeColorName(),
            'message' => fake()->sentence(),
            'consent' => true,
            'otp_verified_at' => now(),
            'source' => 'test',
        ];
    }

    public function sell(): static
    {
        return $this->state(fn () => ['type' => RequestType::SELL->value]);
    }

    public function buy(): static
    {
        return $this->state(fn () => ['type' => RequestType::BUY->value]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::PENDING_VERIFICATION->value,
            'otp_verified_at' => null,
        ]);
    }
}
