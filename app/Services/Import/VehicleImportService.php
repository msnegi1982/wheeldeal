<?php

namespace App\Services\Import;

use App\Enums\ImportStatus;
use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Feature;
use App\Models\FuelType;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class VehicleImportService
{
    /**
     * Column order used by the downloadable template. Extra columns are ignored
     * and columns may appear in any order.
     *
     * @var array<int, string>
     */
    public const HEADERS = [
        'title',
        'brand',
        'model',
        'variant',
        'category',
        'fuel_type',
        'city',
        'year',
        'price',
        'original_price',
        'km_driven',
        'owner_count',
        'transmission',
        'seats',
        'colour',
        'registration_state',
        'registration_year',
        'engine_cc',
        'power_bhp',
        'torque_nm',
        'mileage_kmpl',
        'description',
        'condition',
        'status',
        'is_featured',
        'is_popular',
        'features',
        'slug',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    /**
     * Human-friendly aliases accepted from spreadsheets.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'brand_name' => 'brand',
        'make' => 'brand',
        'model_name' => 'model',
        'vehicle_model' => 'model',
        'variant_name' => 'variant',
        'body_type' => 'category',
        'fuel' => 'fuel_type',
        'city_name' => 'city',
        'kms' => 'km_driven',
        'kilometers' => 'km_driven',
        'km' => 'km_driven',
        'owners' => 'owner_count',
        'registration' => 'registration_state',
        'feature' => 'features',
        'feature_list' => 'features',
    ];

    private const CHUNK_SIZE = 250;

    /** @var array<string, Brand> */
    private array $brands = [];

    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, City> */
    private array $cities = [];

    /** @var array<string, Feature> */
    private array $features = [];

    /** @var array<string, FuelType> */
    private array $fuelTypes = [];

    /** @var array<int, array<string, VehicleModel>> */
    private array $models = [];

    /** @var array<int, array<string, VehicleVariant>> */
    private array $variants = [];

    private int $estimatedRows = 0;

    public function process(ImportBatch $batch): void
    {
        $batch->refresh();

        $batch->forceFill([
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
            'finished_at' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
            'error_report_path' => null,
        ])->save();

        $batch->errors()->delete();

        try {
            $disk = Storage::disk($batch->disk ?: 'local');

            if (! $disk->exists($batch->stored_path)) {
                throw new RuntimeException('The uploaded file could not be found. Upload it again.');
            }

            $path = $disk->path($batch->stored_path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (! in_array($extension, ['csv', 'txt', 'xlsx', 'xls', 'ods'], true)) {
                throw new RuntimeException('Unsupported file type. Upload a CSV, XLSX or XLS file.');
            }

            $this->loadReferenceData();

            $this->estimatedRows = max(0, $this->estimateTotalRows($path, $extension) - 1);

            $batch->forceFill(['total_rows' => $this->estimatedRows])->save();

            $processed = 0;
            $successful = 0;
            $failed = 0;
            $sinceFlush = 0;

            foreach ($this->readRows($path, $extension) as $rowNumber => $row) {
                $processed++;

                if ($this->processRow($batch, $rowNumber, $row)) {
                    $successful++;
                } else {
                    $failed++;
                }

                $sinceFlush++;

                if ($sinceFlush >= 25) {
                    $this->flushProgress($batch, $processed, $successful, $failed);
                    $sinceFlush = 0;
                }
            }

            if ($processed === 0) {
                $this->recordError(
                    $batch,
                    1,
                    null,
                    'The file contains a header row but no vehicle rows.',
                    []
                );

                $failed = 1;
            }

            $reportPath = $this->writeErrorReport($batch);

            $batch->forceFill([
                'total_rows' => max($processed, 1),
                'processed_rows' => max($processed, 1),
                'success_rows' => $successful,
                'error_rows' => $failed,
                'error_report_path' => $reportPath,
                'status' => $successful > 0 ? ImportStatus::COMPLETED : ImportStatus::FAILED,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $this->recordError(
                $batch,
                0,
                null,
                'Import failed: '.Str::limit($e->getMessage(), 1000),
                []
            );

            $reportPath = $this->writeErrorReport($batch);

            $batch->forceFill([
                'status' => ImportStatus::FAILED,
                'error_rows' => max(1, $batch->error_rows),
                'error_report_path' => $reportPath,
                'finished_at' => now(),
            ])->save();
        }
    }

    private function loadReferenceData(): void
    {
        $this->brands = $this->index(Brand::query()->get(), ['name', 'slug']);
        $this->categories = $this->index(Category::query()->get(), ['name', 'slug']);
        $this->cities = $this->index(City::query()->get(), ['name', 'slug']);
        $this->fuelTypes = $this->index(FuelType::query()->get(), ['name', 'slug']);
        $this->features = $this->index(Feature::query()->get(), ['name', 'slug']);

        $models = VehicleModel::query()->get();
        $variants = VehicleVariant::query()->get();

        foreach ($models as $model) {
            $this->models[$model->brand_id] = $this->index(
                $models->where('brand_id', $model->brand_id),
                ['name', 'slug']
            );
        }

        foreach ($variants as $variant) {
            $this->variants[$variant->vehicle_model_id] = $this->index(
                $variants->where('vehicle_model_id', $variant->vehicle_model_id),
                ['name', 'slug']
            );
        }
    }

    /**
     * @param  Collection<int, mixed>  $records
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function index(Collection $records, array $fields): array
    {
        $indexed = [];

        foreach ($records as $record) {
            $indexed[(string) $record->getKey()] = $record;

            foreach ($fields as $field) {
                $value = $record->{$field} ?? null;

                if (is_string($value) && $value !== '') {
                    $indexed[$this->normaliseKey($value)] = $record;
                }
            }
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function lookup(array $map, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $map[$this->normaliseKey($value)] ?? null;
    }

    private function normaliseKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function estimateTotalRows(string $path, string $extension): int
    {
        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($path, 'rb');

            if ($handle === false) {
                return 0;
            }

            $count = 0;

            while (fgets($handle) !== false) {
                $count++;
            }

            fclose($handle);

            return $count;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $info = $reader->listWorksheetInfo($path);

        return (int) ($info[0]['totalRows'] ?? 0);
    }

    /**
     * @return \Generator<int, array<string, string|null>>
     */
    private function readRows(string $path, string $extension): \Generator
    {
        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsvRows($path);
        }

        return $this->readSpreadsheetRows($path);
    }

    /**
     * @return \Generator<int, array<string, string|null>>
     */
    private function readCsvRows(string $path): \Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The CSV file could not be opened.');
        }

        $header = null;
        $rowNumber = 0;
        $delimiter = $this->detectDelimiter($path);

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($rowNumber === 1) {
                $header = $this->normaliseHeader($values);
                $this->assertHeader($header);

                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            yield $rowNumber => $this->combineRow($header, $values);
        }

        fclose($handle);
    }

    /**
     * @return \Generator<int, array<string, string|null>>
     */
    private function readSpreadsheetRows(string $path): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($path);
        $totalRows = (int) ($info[0]['totalRows'] ?? 0);

        if ($totalRows === 0) {
            throw new RuntimeException('The spreadsheet is empty.');
        }

        $header = null;
        $start = 1;

        while ($start <= $totalRows) {
            $end = min($start + self::CHUNK_SIZE - 1, $totalRows);

            $reader->setReadFilter(new ChunkReadFilter($start, $end));

            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $maxColumn = $sheet->getHighestColumn();

            $rows = $sheet->rangeToArray(
                "A{$start}:{$maxColumn}{$end}",
                null,
                true,
                true,
                false
            );

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);

            foreach ($rows as $offset => $values) {
                $rowNumber = $start + $offset;

                if ($header === null) {
                    $header = $this->normaliseHeader($values);
                    $this->assertHeader($header);

                    continue;
                }

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                yield $rowNumber => $this->combineRow($header, $values);
            }

            $start = $end + 1;
        }
    }

    private function detectDelimiter(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $sample = preg_replace('/^\xEF\xBB\xBF/', '', $sample) ?? $sample;
        $firstLine = strtok($sample, "\r\n") ?: $sample;

        $counts = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];

        arsort($counts);

        return array_key_first($counts) ?: ',';
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    private function normaliseHeader(array $row): array
    {
        return array_map(function ($value) {
            $value = $this->stringValue($value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            $value = Str::lower(trim($value));
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
            $value = trim($value, '_');

            return self::ALIASES[$value] ?? $value;
        }, $row);
    }

    /**
     * @param  array<int, string>  $header
     */
    private function assertHeader(array $header): void
    {
        $required = ['brand', 'model', 'year', 'price'];
        $missing = array_values(array_diff($required, $header));

        if ($missing !== []) {
            throw new RuntimeException(
                'The header row is missing required columns: '.implode(', ', $missing).'.'
            );
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, mixed>  $values
     * @return array<string, string|null>
     */
    private function combineRow(array $header, array $values): array
    {
        $row = [];

        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $value = $this->stringValue($values[$index] ?? null);
            $row[$column] = $value === '' ? null : $value;
        }

        return $row;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->stringValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $value = implode(' ', array_map(fn ($item) => $this->stringValue($item), $value));
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    /**
     * Validate and persist a single row. Returns true when the row was saved.
     *
     * @param  array<string, string|null>  $row
     */
    private function processRow(ImportBatch $batch, int $rowNumber, array $row): bool
    {
        $errors = [];

        $brand = $this->lookup($this->brands, $this->value($row, 'brand'));

        if (! $brand) {
            $errors[] = ['brand', 'Brand "'.($this->value($row, 'brand') ?: '—').'" was not found. Create it in Brands first.'];
        }

        $model = null;

        if ($brand) {
            $model = $this->lookup($this->models[$brand->id] ?? [], $this->value($row, 'model'));
        }

        if ($brand && ! $model) {
            $errors[] = ['model', 'Model "'.($this->value($row, 'model') ?: '—').'" was not found for '.$brand->name.'.'];
        }

        $variant = $model
            ? $this->lookup($this->variants[$model->id] ?? [], $this->value($row, 'variant'))
            : null;

        if ($model && $this->value($row, 'variant') && ! $variant) {
            $errors[] = ['variant', 'Variant "'.$this->value($row, 'variant').'" was not found for this model.'];
        }

        $category = $this->lookup($this->categories, $this->value($row, 'category')) ?? $model?->category;

        if (! $category) {
            $errors[] = ['category', 'Body type "'.($this->value($row, 'category') ?: '—').'" was not found.'];
        }

        $fuelType = $this->lookup($this->fuelTypes, $this->value($row, 'fuel_type')) ?? $variant?->fuelType;

        if (! $fuelType) {
            $errors[] = ['fuel_type', 'Fuel type "'.($this->value($row, 'fuel_type') ?: '—').'" was not found.'];
        }

        $city = $this->lookup($this->cities, $this->value($row, 'city'));

        if (! $city) {
            $errors[] = ['city', 'City "'.($this->value($row, 'city') ?: '—').'" was not found.'];
        }

        $transmission = $this->normaliseTransmission($this->value($row, 'transmission'))
            ?? $variant?->transmission?->value;

        if (! $transmission) {
            $errors[] = ['transmission', 'Transmission must be one of: manual, automatic, amt, cvt, dct.'];
        }

        $condition = $this->normaliseCondition($this->value($row, 'condition')) ?? VehicleCondition::GOOD->value;
        $status = $this->normaliseStatus($this->value($row, 'status')) ?? VehicleStatus::DRAFT->value;

        $featureIds = [];
        $featureNames = $this->splitFeatures($this->value($row, 'features'));

        foreach ($featureNames as $featureName) {
            $feature = $this->lookup($this->features, $featureName);

            if ($feature) {
                $featureIds[] = $feature->id;
            } else {
                $errors[] = ['features', 'Feature "'.$featureName.'" was not found. Create it in Features first.'];
            }
        }

        $publishedAt = null;
        $publishedRaw = $this->value($row, 'published_at');

        if ($publishedRaw) {
            try {
                $publishedAt = Carbon::parse($publishedRaw);
            } catch (Throwable) {
                $errors[] = ['published_at', 'Publish date "'.$publishedRaw.'" could not be understood.'];
            }
        }

        $year = $this->toInteger($this->value($row, 'year'));
        $price = $this->toInteger($this->value($row, 'price'));
        $originalPrice = $this->toInteger($this->value($row, 'original_price'));
        $kmDriven = $this->toInteger($this->value($row, 'km_driven'));
        $ownerCount = $this->toInteger($this->value($row, 'owner_count'));
        $seats = $this->toInteger($this->value($row, 'seats')) ?? $variant?->seats;
        $registrationYear = $this->toInteger($this->value($row, 'registration_year'));
        $engineCc = $this->toInteger($this->value($row, 'engine_cc')) ?? $variant?->engine_cc;
        $torqueNm = $this->toInteger($this->value($row, 'torque_nm')) ?? $variant?->torque_nm;
        $powerBhp = $this->toDecimal($this->value($row, 'power_bhp')) ?? $variant?->power_bhp;
        $mileage = $this->toDecimal($this->value($row, 'mileage_kmpl')) ?? $variant?->mileage_kmpl;

        $title = $this->value($row, 'title');
        $requestedSlug = $this->value($row, 'slug');

        if ($title === null && $brand && $model) {
            $title = trim(implode(' ', array_filter([
                $year,
                $brand->name,
                $model->name,
                $variant?->name,
            ])));
        }

        $data = [
            'title' => $title,
            'brand_id' => $brand?->id,
            'vehicle_model_id' => $model?->id,
            'vehicle_variant_id' => $variant?->id,
            'category_id' => $category?->id,
            'fuel_type_id' => $fuelType?->id,
            'city_id' => $city?->id,
            'year' => $year,
            'price' => $price,
            'original_price' => $originalPrice,
            'km_driven' => $kmDriven,
            'owner_count' => $ownerCount ?? 1,
            'transmission' => $transmission,
            'seats' => $seats ?? 5,
            'colour' => $this->value($row, 'colour'),
            'registration_state' => $this->value($row, 'registration_state'),
            'registration_year' => $registrationYear,
            'engine_cc' => $engineCc,
            'power_bhp' => $powerBhp,
            'torque_nm' => $torqueNm,
            'mileage_kmpl' => $mileage,
            'description' => $this->value($row, 'description'),
            'condition' => $condition,
            'status' => $status,
            'is_featured' => $this->toBoolean($this->value($row, 'is_featured')),
            'is_popular' => $this->toBoolean($this->value($row, 'is_popular')),
            'meta_title' => $this->value($row, 'meta_title'),
            'meta_description' => $this->value($row, 'meta_description'),
            'published_at' => $publishedAt,
        ];

        $validator = Validator::make($data, [
            'title' => ['required', 'string', 'max:180'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'vehicle_model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            'vehicle_variant_id' => ['nullable', 'integer', 'exists:vehicle_variants,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'fuel_type_id' => ['required', 'integer', 'exists:fuel_types,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'price' => ['required', 'integer', 'min:1000'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'km_driven' => ['required', 'integer', 'min:0', 'max:2000000'],
            'owner_count' => ['required', 'integer', 'between:1,10'],
            'transmission' => ['required', Rule::in(array_column(Transmission::cases(), 'value'))],
            'seats' => ['required', 'integer', 'between:2,12'],
            'colour' => ['nullable', 'string', 'max:50'],
            'registration_state' => ['nullable', 'string', 'max:80'],
            'registration_year' => ['nullable', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'engine_cc' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'power_bhp' => ['nullable', 'numeric', 'min:10', 'max:2000'],
            'torque_nm' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'mileage_kmpl' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'condition' => ['required', Rule::in(array_column(VehicleCondition::cases(), 'value'))],
            'status' => ['required', Rule::in(array_column(VehicleStatus::cases(), 'value'))],
            'is_featured' => ['boolean'],
            'is_popular' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $column => $messages) {
                foreach ($messages as $message) {
                    $errors[] = [$column, $message];
                }
            }
        }

        if ($errors !== []) {
            foreach ($errors as [$column, $message]) {
                $this->recordError($batch, $rowNumber, $column, $message, $row);
            }

            return false;
        }

        $updateExisting = (bool) ($batch->options['update_existing'] ?? false);
        $existing = null;

        if ($requestedSlug !== null) {
            $requestedSlug = Str::slug($requestedSlug);

            $existing = Vehicle::withTrashed()
                ->where('vehicle_model_id', $model->id)
                ->where('slug', $requestedSlug)
                ->first();

            if ($existing && $existing->trashed()) {
                $this->recordError(
                    $batch,
                    $rowNumber,
                    'slug',
                    'A deleted vehicle already uses this slug. Restore it or choose a different slug.',
                    $row
                );

                return false;
            }

            if ($existing && ! $updateExisting) {
                $this->recordError(
                    $batch,
                    $rowNumber,
                    'slug',
                    'A vehicle with this slug already exists. Enable "update existing" to overwrite it.',
                    $row
                );

                return false;
            }
        }

        $baseSlug = $requestedSlug ?: Str::slug(implode(' ', array_filter([
            $year,
            $brand->name,
            $model->name,
            $variant?->name,
            $city?->name,
        ])));

        $data['slug'] = $existing
            ? $requestedSlug
            : $this->uniqueSlug($model->id, $baseSlug);

        try {
            DB::transaction(function () use ($batch, $existing, $data, $featureIds) {
                if ($existing) {
                    $existing->update($data);
                    $vehicle = $existing;
                } else {
                    $data['created_by'] = $batch->user_id;
                    $vehicle = Vehicle::create($data);
                }

                $vehicle->features()->sync($featureIds);
            });
        } catch (Throwable $e) {
            $this->recordError(
                $batch,
                $rowNumber,
                null,
                'The vehicle could not be saved: '.Str::limit($e->getMessage(), 500),
                $row
            );

            return false;
        }

        return true;
    }

    private function uniqueSlug(int $modelId, string $base): string
    {
        $base = Str::limit(Str::slug($base) ?: 'vehicle', 180, '');
        $candidate = $base;
        $suffix = 2;

        while (Vehicle::withTrashed()->where('vehicle_model_id', $modelId)->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function flushProgress(ImportBatch $batch, int $processed, int $successful, int $failed): void
    {
        $batch->forceFill([
            'processed_rows' => $processed,
            'success_rows' => $successful,
            'error_rows' => $failed,
        ])->save();
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function recordError(
        ImportBatch $batch,
        int $rowNumber,
        ?string $column,
        string $message,
        array $row
    ): void {
        ImportError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $rowNumber,
            'column' => $column,
            'message' => $message,
            'row_data' => $row === [] ? null : $row,
        ]);
    }

    private function writeErrorReport(ImportBatch $batch): ?string
    {
        if (! $batch->errors()->exists()) {
            return null;
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return null;
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Row', 'Column', 'Message', 'Row data']);

        foreach ($batch->errors()->orderBy('row_number')->orderBy('id')->cursor() as $error) {
            fputcsv($handle, [
                $error->row_number,
                $error->column,
                $error->message,
                $error->row_data ? json_encode($error->row_data, JSON_UNESCAPED_UNICODE) : null,
            ]);
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        if ($contents === false) {
            return null;
        }

        $path = 'imports/reports/'.$batch->uuid.'-errors.csv';
        Storage::disk($batch->disk ?: 'local')->put($path, $contents);

        return $path;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function value(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if (! is_string($value)) {
            return $value === null ? null : (string) $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function toInteger(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $number = $this->toDecimal($value);

        return $number === null ? null : (int) round($number);
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $multiplier = 1.0;

        if (preg_match('/(lakh|lac|lakhs)/i', $value)) {
            $multiplier = 100000.0;
        } elseif (preg_match('/(crore|cr)\b/i', $value)) {
            $multiplier = 10000000.0;
        }

        $number = preg_replace('/[^0-9.\-]/', '', $value);

        if ($number === null || $number === '' || ! is_numeric($number)) {
            return null;
        }

        return round((float) $number * $multiplier, 2);
    }

    private function toBoolean(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function normaliseTransmission(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        $value = match ($value) {
            'auto', 'automatic transmission', 'a/t' => Transmission::AUTOMATIC->value,
            'manual transmission', 'm/t' => Transmission::MANUAL->value,
            default => $value,
        };

        return in_array($value, array_column(Transmission::cases(), 'value'), true) ? $value : null;
    }

    private function normaliseCondition(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        return in_array($value, array_column(VehicleCondition::cases(), 'value'), true) ? $value : null;
    }

    private function normaliseStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtolower(trim($value));

        $value = match ($value) {
            'active', 'live', 'publish' => VehicleStatus::PUBLISHED->value,
            default => $value,
        };

        return in_array($value, array_column(VehicleStatus::cases(), 'value'), true) ? $value : null;
    }

    /**
     * @return array<int, string>
     */
    private function splitFeatures(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $parts = preg_split('/[|;,\n]+/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $part) => $part !== ''));
    }
}
