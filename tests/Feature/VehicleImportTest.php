<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Feature;
use App\Models\FuelType;
use App\Models\ImportBatch;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use App\Services\Import\VehicleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class VehicleImportTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private Category $category;

    private City $city;

    private FuelType $fuel;

    private VehicleModel $model;

    private VehicleVariant $variant;

    private Feature $sunroof;

    private Feature $camera;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create(['name' => 'Maruti Suzuki', 'slug' => 'maruti-suzuki']);
        $this->category = Category::factory()->create(['name' => 'Hatchback', 'slug' => 'hatchback']);
        $this->city = City::factory()->create(['name' => 'Bengaluru', 'slug' => 'bengaluru']);
        $this->fuel = FuelType::factory()->create(['name' => 'Petrol', 'slug' => 'petrol']);
        $this->model = VehicleModel::factory()->create([
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'name' => 'Swift',
            'slug' => 'swift',
        ]);
        $this->variant = VehicleVariant::factory()->create([
            'vehicle_model_id' => $this->model->id,
            'fuel_type_id' => $this->fuel->id,
            'name' => 'VXi',
            'slug' => 'vxi',
            'transmission' => Transmission::MANUAL,
            'seats' => 5,
        ]);
        $this->sunroof = Feature::factory()->create(['name' => 'Sunroof', 'slug' => 'sunroof']);
        $this->camera = Feature::factory()->create(['name' => 'Reverse Camera', 'slug' => 'reverse-camera']);
    }

    public function test_csv_rows_are_imported_with_resolved_master_data(): void
    {
        $batch = $this->batch($this->csv([
            'brand' => 'maruti suzuki',
            'model' => 'Swift',
            'variant' => 'VXi',
            'category' => 'Hatchback',
            'fuel' => 'Petrol',
            'city' => 'Bengaluru',
            'year' => 2022,
            'price' => '₹6,50,000',
            'km_driven' => '24,000 km',
            'transmission' => 'Manual',
            'features' => 'Sunroof|Reverse Camera',
            'status' => 'published',
        ]));

        app(VehicleImportService::class)->process($batch);

        $batch->refresh();
        $vehicle = Vehicle::query()->sole();

        $this->assertSame(ImportStatus::COMPLETED, $batch->status);
        $this->assertSame(1, $batch->processed_rows);
        $this->assertSame(1, $batch->success_rows);
        $this->assertSame(0, $batch->error_rows);
        $this->assertNull($batch->error_report_path);

        $this->assertSame('2022 Maruti Suzuki Swift VXi', $vehicle->title);
        $this->assertSame('2022-maruti-suzuki-swift-vxi-bengaluru', $vehicle->slug);
        $this->assertSame(650000, $vehicle->price);
        $this->assertSame(24000, $vehicle->km_driven);
        $this->assertSame($this->brand->id, $vehicle->brand_id);
        $this->assertSame($this->model->id, $vehicle->vehicle_model_id);
        $this->assertSame($this->variant->id, $vehicle->vehicle_variant_id);
        $this->assertSame($this->city->id, $vehicle->city_id);
        $this->assertSame(Transmission::MANUAL, $vehicle->transmission);
        $this->assertSame(VehicleStatus::PUBLISHED, $vehicle->status);
        $this->assertNotNull($vehicle->published_at);
        $this->assertEqualsCanonicalizing(
            [$this->sunroof->id, $this->camera->id],
            $vehicle->features()->pluck('features.id')->all()
        );
    }

    public function test_bad_rows_are_reported_without_stopping_the_batch(): void
    {
        $good = $this->csv([
            'brand' => 'Maruti Suzuki',
            'model' => 'Swift',
            'category' => 'Hatchback',
            'fuel_type' => 'Petrol',
            'city' => 'Bengaluru',
            'year' => 2021,
            'price' => 600000,
            'km_driven' => 30000,
            'transmission' => 'manual',
        ]);

        $bad = $this->csv([
            'brand' => 'Unknown Motors',
            'model' => 'Ghost',
            'category' => 'Hatchback',
            'fuel_type' => 'Petrol',
            'city' => 'Bengaluru',
            'year' => 1970,
            'price' => 500,
            'km_driven' => 30000,
            'transmission' => 'manual',
        ]);

        $batch = $this->batch($this->mergeCsv($good, $bad));

        app(VehicleImportService::class)->process($batch);

        $batch->refresh();

        $this->assertSame(ImportStatus::COMPLETED, $batch->status);
        $this->assertSame(2, $batch->processed_rows);
        $this->assertSame(1, $batch->success_rows);
        $this->assertSame(1, $batch->error_rows);
        $this->assertSame(1, Vehicle::query()->count());

        $errors = $batch->errors()->get();

        $this->assertGreaterThanOrEqual(2, $errors->count());
        $this->assertTrue($errors->contains(fn ($error) => $error->column === 'brand' && str_contains($error->message, 'not found')));
        $this->assertTrue($errors->contains(fn ($error) => $error->column === 'year'));
        $this->assertNotNull($batch->error_report_path);
        $this->assertTrue(Storage::disk('local')->exists($batch->error_report_path));
        $this->assertStringContainsString('Unknown Motors', Storage::disk('local')->get($batch->error_report_path));
    }

    public function test_existing_slug_is_rejected_unless_update_existing_is_enabled(): void
    {
        $existing = Vehicle::factory()->create([
            'brand_id' => $this->brand->id,
            'vehicle_model_id' => $this->model->id,
            'category_id' => $this->category->id,
            'fuel_type_id' => $this->fuel->id,
            'city_id' => $this->city->id,
            'title' => 'Old Swift',
            'slug' => 'my-swift',
            'price' => 500000,
        ]);

        $csv = $this->csv([
            'brand' => 'Maruti Suzuki',
            'model' => 'Swift',
            'category' => 'Hatchback',
            'fuel_type' => 'Petrol',
            'city' => 'Bengaluru',
            'year' => 2022,
            'price' => 700000,
            'km_driven' => 20000,
            'transmission' => 'manual',
            'slug' => 'my-swift',
        ]);

        $rejected = $this->batch($csv);
        app(VehicleImportService::class)->process($rejected);

        $this->assertSame(ImportStatus::FAILED, $rejected->fresh()->status);
        $this->assertSame(500000, $existing->fresh()->price);
        $this->assertTrue(
            $rejected->errors()->where('column', 'slug')->exists()
        );

        $accepted = $this->batch($csv, options: ['update_existing' => true]);
        app(VehicleImportService::class)->process($accepted);

        $this->assertSame(ImportStatus::COMPLETED, $accepted->fresh()->status);
        $this->assertSame(1, Vehicle::query()->count());
        $this->assertSame(700000, $existing->fresh()->price);
    }

    public function test_large_xlsx_files_are_read_in_chunks(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [
            'brand', 'model', 'category', 'fuel_type', 'city', 'year',
            'price', 'km_driven', 'transmission', 'colour',
        ];

        $sheet->fromArray($headers, null, 'A1');

        for ($row = 2; $row <= 262; $row++) {
            $sheet->fromArray([
                'Maruti Suzuki',
                'Swift',
                'Hatchback',
                'Petrol',
                'Bengaluru',
                2022,
                650000 + $row,
                24000,
                'manual',
                'Red',
            ], null, 'A'.$row);
        }

        $path = 'imports/tests/'.Str::uuid().'.xlsx';
        Storage::disk('local')->makeDirectory('imports/tests');
        (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($path));
        $spreadsheet->disconnectWorksheets();

        $batch = ImportBatch::create([
            'user_id' => User::factory()->create()->id,
            'original_name' => 'bulk.xlsx',
            'stored_path' => $path,
            'disk' => 'local',
            'status' => ImportStatus::PENDING,
        ]);

        app(VehicleImportService::class)->process($batch);

        $batch->refresh();

        $this->assertSame(ImportStatus::COMPLETED, $batch->status);
        $this->assertSame(261, $batch->processed_rows);
        $this->assertSame(261, $batch->success_rows);
        $this->assertSame(0, $batch->error_rows);
        $this->assertSame(261, Vehicle::query()->count());
        $this->assertSame(
            261,
            Vehicle::query()->distinct()->count('slug')
        );
    }

    public function test_missing_headers_fail_the_batch_with_a_clear_error(): void
    {
        $batch = $this->batch("model,price\nSwift,600000\n");

        app(VehicleImportService::class)->process($batch);

        $batch->refresh();

        $this->assertSame(ImportStatus::FAILED, $batch->status);
        $this->assertSame(0, Vehicle::query()->count());
        $this->assertTrue(
            $batch->errors()->where('message', 'like', '%missing required columns%')->exists()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function batch(string $contents, array $options = []): ImportBatch
    {
        $path = 'imports/tests/'.Str::uuid().'.csv';
        Storage::disk('local')->makeDirectory('imports/tests');
        Storage::disk('local')->put($path, $contents);

        return ImportBatch::create([
            'user_id' => User::factory()->create()->id,
            'original_name' => 'vehicles.csv',
            'stored_path' => $path,
            'disk' => 'local',
            'status' => ImportStatus::PENDING,
            'options' => $options,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function csv(array $values): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($values));
        fputcsv($handle, array_values($values));
        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return (string) $contents;
    }

    private function mergeCsv(string ...$documents): string
    {
        $header = '';
        $body = '';

        foreach ($documents as $document) {
            [$firstLine, $rest] = array_pad(explode("\n", $document, 2), 2, '');

            if ($header === '') {
                $header = $firstLine;
            }

            $body .= $rest;
        }

        return trim($header."\n".$body, "\n")."\n";
    }
}
