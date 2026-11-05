<?php

namespace App\Support;

use App\Services\Import\VehicleImportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleImportTemplate
{
    public static function download(): StreamedResponse
    {
        return CsvExporter::download(
            'wheeldeal-vehicle-import-template.csv',
            VehicleImportService::HEADERS,
            self::rows()
        );
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public static function rows(): array
    {
        $samples = [
            [
                'title' => '',
                'brand' => 'Maruti Suzuki',
                'model' => 'Swift',
                'variant' => 'VXi',
                'category' => 'Hatchback',
                'fuel_type' => 'Petrol',
                'city' => 'Bengaluru',
                'year' => 2022,
                'price' => 650000,
                'original_price' => 780000,
                'km_driven' => 24000,
                'owner_count' => 1,
                'transmission' => 'manual',
                'seats' => 5,
                'colour' => 'Fire Red',
                'registration_state' => 'Karnataka',
                'registration_year' => 2022,
                'engine_cc' => 1197,
                'power_bhp' => 88,
                'torque_nm' => 113,
                'mileage_kmpl' => 22.5,
                'description' => 'Single-owner hatchback with full service history and no accident repairs.',
                'condition' => 'excellent',
                'status' => 'published',
                'is_featured' => 'no',
                'is_popular' => 'no',
                'features' => 'Reverse Camera|Alloy Wheels|Automatic Climate Control',
                'slug' => '',
                'meta_title' => '',
                'meta_description' => '',
                'published_at' => '',
            ],
            [
                'title' => '',
                'brand' => 'Hyundai',
                'model' => 'Creta',
                'variant' => 'SX (O)',
                'category' => 'SUV',
                'fuel_type' => 'Diesel',
                'city' => 'Mumbai',
                'year' => 2023,
                'price' => 1780000,
                'original_price' => 1950000,
                'km_driven' => 16000,
                'owner_count' => 1,
                'transmission' => 'automatic',
                'seats' => 5,
                'colour' => 'Abyss Black',
                'registration_state' => 'Maharashtra',
                'registration_year' => 2023,
                'engine_cc' => 1493,
                'power_bhp' => 114,
                'torque_nm' => 250,
                'mileage_kmpl' => 18.4,
                'description' => 'Top-spec automatic SUV with panoramic sunroof and complete service records.',
                'condition' => 'excellent',
                'status' => 'draft',
                'is_featured' => 'yes',
                'is_popular' => 'no',
                'features' => 'Sunroof|Ventilated Seats|ADAS|Wireless Charging',
                'slug' => '',
                'meta_title' => '',
                'meta_description' => '',
                'published_at' => '',
            ],
        ];

        return array_map(
            fn (array $row) => array_map(
                fn (string $header) => $row[$header] ?? null,
                VehicleImportService::HEADERS
            ),
            $samples
        );
    }
}
