<?php

namespace Database\Seeders;

use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Feature;
use App\Models\FuelType;
use App\Models\SeoPage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogDemoSeeder extends Seeder
{
    private array $brands = [
        ['Toyota', 'toyota', true],
        ['Hyundai', 'hyundai', true],
        ['Maruti Suzuki', 'maruti-suzuki', true],
        ['Mahindra', 'mahindra', true],
        ['Kia', 'kia', true],
        ['Tata', 'tata', false],
        ['Honda', 'honda', false],
        ['MG', 'mg', false],
    ];

    private array $models = [
        'toyota' => [
            ['Fortuner', 'suv', 'diesel', 'automatic', 2022, 3350000, 42000, 'Pearl White', true],
            ['Glanza', 'hatchback', 'petrol', 'manual', 2021, 720000, 28000, 'Cafe White', true],
            ['Innova Hycross', 'muv', 'hybrid', 'automatic', 2023, 2850000, 18000, 'Blackish Ageha', false],
        ],
        'hyundai' => [
            ['Creta', 'suv', 'diesel', 'automatic', 2023, 1780000, 16000, 'Abyss Black', true],
            ['Verna', 'sedan', 'petrol', 'manual', 2021, 980000, 32000, 'Titan Grey', true],
            ['Ioniq 5', 'ev', 'electric', 'automatic', 2023, 3900000, 12000, 'Gravity Gold', false],
        ],
        'maruti-suzuki' => [
            ['Swift', 'hatchback', 'petrol', 'manual', 2022, 650000, 24000, 'Fire Red', true],
            ['Brezza', 'suv', 'petrol', 'automatic', 2022, 1120000, 30000, 'Magma Grey', true],
            ['Ertiga', 'muv', 'cng', 'manual', 2021, 1050000, 52000, 'Splendid Silver', false],
        ],
        'mahindra' => [
            ['XUV700', 'suv', 'diesel', 'automatic', 2023, 2450000, 21000, 'Midnight Black', true],
            ['Thar', 'suv', 'diesel', 'manual', 2021, 1650000, 36000, 'Red Rage', true],
            ['Scorpio-N', 'suv', 'diesel', 'automatic', 2023, 2250000, 19000, 'Everest White', false],
        ],
        'kia' => [
            ['Seltos', 'suv', 'petrol', 'automatic', 2022, 1550000, 31000, 'Gravity Grey', true],
            ['Carens', 'muv', 'diesel', 'automatic', 2023, 1680000, 17000, 'Clear White', false],
        ],
        'tata' => [
            ['Nexon EV', 'ev', 'electric', 'automatic', 2022, 1350000, 27000, 'Empowered Oxide', true],
            ['Harrier', 'suv', 'diesel', 'automatic', 2021, 1980000, 44000, 'Calypso Red', false],
        ],
        'honda' => [
            ['City', 'sedan', 'petrol', 'automatic', 2022, 1280000, 26000, 'Platinum White', true],
            ['Elevate', 'suv', 'petrol', 'manual', 2023, 1420000, 15000, 'Radiant Red', false],
        ],
        'mg' => [
            ['Hector', 'suv', 'petrol', 'automatic', 2022, 1620000, 33000, 'Starry Sky', false],
            ['ZS EV', 'ev', 'electric', 'automatic', 2022, 1480000, 29000, 'Currant Red', false],
        ],
    ];

    public function run(): void
    {
        $features = Feature::all();
        $cities = City::all();
        $createdBy = User::query()->where('email', 'admin@wheeldeal.test')->value('id')
            ?? User::query()->value('id');
        $vehicleIndex = 0;

        foreach ($this->brands as [$brandName, $brandSlug, $featured]) {
            $brand = Brand::updateOrCreate(['slug' => $brandSlug], [
                'name' => $brandName,
                'short_description' => "Verified used {$brandName} cars with transparent pricing.",
                'description' => "{$brandName} is a popular choice for Indian buyers looking for reliability, comfort and strong resale value. Browse our hand-picked {$brandName} listings with complete specs, photos and 360° views.",
                'meta_title' => "Used {$brandName} cars — prices, photos & specs | WheelDeal",
                'meta_description' => "Explore verified used {$brandName} cars across India. Filter by budget, fuel, transmission and city.",
                'is_featured' => $featured,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            if (! $brand->hasMedia('logo')) {
                $logo = $this->makeImage($brandName, 320, 320, '#111111', '#FFC107', true);
                $brand->addMedia($logo)
                    ->usingFileName($brandSlug.'-logo.jpg')
                    ->withCustomProperties(['alt' => $brandName.' logo'])
                    ->toMediaCollection('logo', 'public');
            }

            foreach ($this->models[$brandSlug] as [$modelName, $categorySlug, $fuelSlug, $transmission, $year, $price, $km, $colour, $featuredVehicle]) {
                $category = Category::where('slug', $categorySlug)->firstOrFail();
                $fuel = FuelType::where('slug', $fuelSlug)->firstOrFail();

                $vehicleModel = VehicleModel::updateOrCreate(
                    ['brand_id' => $brand->id, 'slug' => Str::slug($modelName)],
                    [
                        'category_id' => $category->id,
                        'name' => $modelName,
                        'description' => "The {$brandName} {$modelName} blends everyday usability with a confident drive. This listing has been inspected and is ready for a test drive.",
                        'meta_title' => "Used {$brandName} {$modelName} — price, specs & photos | WheelDeal",
                        'meta_description' => "Find a verified used {$brandName} {$modelName} with photos, specifications, 360° view and seller contact.",
                        'is_active' => true,
                    ]
                );

                $variantName = match ($categorySlug) {
                    'ev' => 'Empowered+',
                    'suv' => 'ZX',
                    'muv' => 'VX',
                    default => 'VXi',
                };

                $variant = VehicleVariant::updateOrCreate(
                    ['vehicle_model_id' => $vehicleModel->id, 'slug' => Str::slug($variantName)],
                    [
                        'fuel_type_id' => $fuel->id,
                        'name' => $variantName,
                        'transmission' => $transmission,
                        'engine_cc' => $fuelSlug === 'electric' ? null : ($categorySlug === 'suv' ? 2199 : 1197),
                        'power_bhp' => $fuelSlug === 'electric' ? 136 : 118,
                        'torque_nm' => $fuelSlug === 'electric' ? 250 : 220,
                        'mileage_kmpl' => $fuelSlug === 'electric' ? null : 17.5,
                        'seats' => $categorySlug === 'muv' ? 7 : 5,
                        'price_min' => max(0, $price - 200000),
                        'price_max' => $price + 300000,
                        'is_active' => true,
                    ]
                );

                $title = "{$year} {$brandName} {$modelName} {$variantName}";
                $slug = Str::slug($title).'-'.(++$vehicleIndex);
                $vehicle = Vehicle::updateOrCreate(['slug' => $slug], [
                    'title' => $title,
                    'brand_id' => $brand->id,
                    'vehicle_model_id' => $vehicleModel->id,
                    'vehicle_variant_id' => $variant->id,
                    'category_id' => $category->id,
                    'fuel_type_id' => $fuel->id,
                    'city_id' => $cities[$vehicleIndex % $cities->count()]->id,
                    'year' => $year,
                    'price' => $price,
                    'original_price' => $price + 120000,
                    'km_driven' => $km,
                    'owner_count' => ($vehicleIndex % 2) + 1,
                    'transmission' => $transmission,
                    'seats' => $categorySlug === 'muv' ? 7 : 5,
                    'colour' => $colour,
                    'registration_state' => 'Karnataka',
                    'registration_year' => $year,
                    'engine_cc' => $fuelSlug === 'electric' ? null : 1497,
                    'power_bhp' => $fuelSlug === 'electric' ? 136 : 118,
                    'torque_nm' => 250,
                    'mileage_kmpl' => $fuelSlug === 'electric' ? null : 17.5,
                    'description' => "This {$year} {$brandName} {$modelName} {$variantName} is a carefully inspected pre-owned vehicle with a clean history. It has covered {$km} km and comes with two keys, a service record and a fresh insurance quote.\n\nHighlights include a well-maintained cabin, responsive powertrain and strong city manners. Book a viewing to inspect the vehicle and take a test drive.",
                    'condition' => VehicleCondition::EXCELLENT->value,
                    'status' => VehicleStatus::PUBLISHED->value,
                    'is_featured' => $featuredVehicle,
                    'is_popular' => $vehicleIndex % 3 === 0,
                    'popularity_score' => 1000 - ($vehicleIndex * 17),
                    'views_count' => 2500 - ($vehicleIndex * 37),
                    'meta_title' => "{$title} for sale — ₹".number_format($price).' | WheelDeal',
                    'meta_description' => "Buy this verified {$title} in {$cities[$vehicleIndex % $cities->count()]->name}. {$km} km, {$colour}, full specs, photos and 360° view.",
                    'published_at' => now()->subDays($vehicleIndex + 1),
                    'created_by' => $createdBy,
                ]);

                $vehicle->features()->sync(
                    $features->shuffle()->take(6)->pluck('id')->all()
                );

                if (! $vehicle->hasMedia('gallery')) {
                    $imageCount = $vehicleIndex <= 4 ? 3 : 2;

                    for ($image = 1; $image <= $imageCount; $image++) {
                        $path = $this->makeImage(
                            $title.' · '.($image === 1 ? 'Front three-quarter' : 'Cabin detail '.$image),
                            1400,
                            880,
                            $image % 2 ? '#111111' : '#1a1a1a',
                            '#FFC107'
                        );

                        $vehicle->addMedia($path)
                            ->usingFileName($vehicle->slug.'-'.$image.'.jpg')
                            ->withCustomProperties([
                                'alt' => "{$title} — ".($image === 1 ? 'front three-quarter exterior' : 'interior detail'),
                                'width' => 1400,
                                'height' => 880,
                            ])
                            ->toMediaCollection('gallery', 'public');
                    }
                }

                if (in_array($vehicleIndex, [1, 2], true) && $vehicle->getMedia('360-frames')->count() === 0) {
                    for ($frame = 1; $frame <= 24; $frame++) {
                        $path = $this->makeImage(
                            '360° · '.str_pad((string) $frame, 2, '0', STR_PAD_LEFT),
                            1000,
                            650,
                            '#111111',
                            '#FFC107',
                            false,
                            $frame
                        );

                        $media = $vehicle->addMedia($path)
                            ->usingFileName($vehicle->slug.'-frame-'.str_pad((string) $frame, 2, '0', STR_PAD_LEFT).'.jpg')
                            ->withCustomProperties([
                                'alt' => "{$title} 360 degree view — frame {$frame}",
                                'width' => 1000,
                                'height' => 650,
                            ])
                            ->toMediaCollection('360-frames', 'public');

                        $media->order_column = $frame;
                        $media->save();
                    }
                }
            }
        }

        $suv = Category::where('slug', 'suv')->first();
        $toyota = Brand::where('slug', 'toyota')->first();

        if ($suv && $toyota) {
            SeoPage::updateOrCreate(['slug' => 'suv-toyota'], [
                'category_id' => $suv->id,
                'brand_id' => $toyota->id,
                'h1' => 'Used Toyota SUVs for sale',
                'meta_title' => 'Used Toyota SUVs — Fortuner & more | WheelDeal',
                'meta_description' => 'Shop verified used Toyota SUVs with transparent pricing, detailed specs, photos and 360° views. Compare Fortuner and other Toyota SUVs.',
                'intro_copy' => 'Toyota SUVs are known for rugged reliability and strong resale value. Explore our verified used Toyota SUV inventory, compare variants and connect with a seller.',
                'is_active' => true,
            ]);
        }
    }

    private function makeImage(
        string $label,
        int $width,
        int $height,
        string $background,
        string $accent,
        bool $logo = false,
        int $frame = 0
    ): string {
        $directory = storage_path('app/demo-media');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $image = imagecreatetruecolor($width, $height);
        $bg = $this->allocate($image, $background);
        $accentColor = $this->allocate($image, $accent);
        $white = imagecolorallocate($image, 245, 245, 245);
        $muted = imagecolorallocate($image, 140, 140, 140);
        $dark = imagecolorallocate($image, 17, 17, 17);

        imagefill($image, 0, 0, $bg);

        for ($x = -$height; $x < $width; $x += 90) {
            imageline($image, $x, 0, $x + $height, $height, $this->allocate($image, $background === '#111111' ? '#1b1b1b' : '#222222'));
        }

        if ($logo) {
            imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width * 0.72), (int) ($height * 0.72), $accentColor);
            $initials = Str::upper(Str::substr($label, 0, 2));
            imagestring($image, 5, (int) ($width / 2) - 14, (int) ($height / 2) - 8, $initials, $dark);
        } else {
            $shift = $frame > 0 ? (int) (sin(deg2rad($frame * 15)) * 24) : 0;
            imagefilledellipse($image, (int) ($width * 0.5) + $shift, (int) ($height * 0.56), (int) ($width * 0.7), (int) ($height * 0.28), $accentColor);
            imagefilledellipse($image, (int) ($width * 0.5) + $shift, (int) ($height * 0.54), (int) ($width * 0.52), (int) ($height * 0.2), $dark);
            imagefilledellipse($image, (int) ($width * 0.33) + $shift, (int) ($height * 0.67), 80, 80, $white);
            imagefilledellipse($image, (int) ($width * 0.67) + $shift, (int) ($height * 0.67), 80, 80, $white);
            imagefilledrectangle($image, 28, 24, 220, 52, $accentColor);
            imagestring($image, 3, 38, 31, 'WHEELDEAL VERIFIED', $dark);
            imagestring($image, 4, 34, $height - 52, Str::limit($label, 62), $white);
            imagestring($image, 2, 34, $height - 30, 'Representative image · inspect before purchase', $muted);
        }

        $path = $directory.'/'.uniqid('demo_', true).'.jpg';
        imagejpeg($image, $path, 88);
        imagedestroy($image);

        return $path;
    }

    private function allocate($image, string $hex): int
    {
        $hex = ltrim($hex, '#');

        return imagecolorallocate(
            $image,
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }
}
