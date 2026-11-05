<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Feature;
use App\Models\FuelType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['SUV', 'suv', 'High-riding family and adventure vehicles.'],
            ['Sedan', 'sedan', 'Comfort-focused three-box cars for city and highway.'],
            ['Hatchback', 'hatchback', 'Compact, efficient cars built for everyday driving.'],
            ['MUV', 'muv', 'Seven-seat people movers and family MPVs.'],
            ['Coupe', 'coupe', 'Low-slung, driver-focused two-door cars.'],
            ['Convertible', 'convertible', 'Open-top motoring and weekend escapes.'],
            ['EV', 'ev', 'Battery-electric cars with zero tailpipe emissions.'],
        ];

        foreach ($categories as $index => [$name, $slug, $description]) {
            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'meta_title' => "Used {$name} cars in India — WheelDeal",
                    'meta_description' => "Browse verified used {$name} cars with prices, photos, specs and 360° views.",
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        $fuels = [
            ['Petrol', 'petrol'], ['Diesel', 'diesel'], ['CNG', 'cng'],
            ['Electric', 'electric'], ['Hybrid', 'hybrid'],
        ];

        foreach ($fuels as $index => [$name, $slug]) {
            FuelType::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $cities = [
            ['Bengaluru', 'Karnataka'], ['Mumbai', 'Maharashtra'], ['New Delhi', 'Delhi'],
            ['Hyderabad', 'Telangana'], ['Chennai', 'Tamil Nadu'], ['Pune', 'Maharashtra'],
            ['Ahmedabad', 'Gujarat'], ['Kolkata', 'West Bengal'], ['Jaipur', 'Rajasthan'],
            ['Kochi', 'Kerala'],
        ];

        foreach ($cities as $index => [$name, $state]) {
            City::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'state' => $state,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $features = [
            ['ABS with EBD', 'safety'], ['6 Airbags', 'safety'], ['Electronic Stability Control', 'safety'],
            ['Sunroof', 'comfort'], ['Ventilated Seats', 'comfort'], ['Automatic Climate Control', 'comfort'],
            ['Reverse Camera', 'technology'], ['Wireless Apple CarPlay / Android Auto', 'technology'],
            ['Connected Car Tech', 'technology'], ['Cruise Control', 'comfort'], ['ADAS', 'safety'],
            ['LED Headlamps', 'exterior'], ['Alloy Wheels', 'exterior'], ['Wireless Charging', 'technology'],
        ];

        foreach ($features as $index => [$name, $category]) {
            Feature::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'category' => $category,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
