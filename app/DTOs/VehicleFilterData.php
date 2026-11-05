<?php

namespace App\DTOs;

use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Immutable-ish value object for public catalog filters.
 *
 * Values are deliberately sanitised instead of validated: unknown or malformed
 * query-string values are ignored so that shared/crawled URLs never 422.
 */
final class VehicleFilterData
{
    public const SORTS = [
        'latest' => 'Newest first',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'year_new' => 'Year: newest first',
        'km_low' => 'Kilometres: low to high',
        'popular' => 'Most popular',
    ];

    public const PER_PAGE = [12, 24, 48];

    public function __construct(
        public ?string $keyword = null,
        public ?string $category = null,
        public ?string $brand = null,
        public ?string $city = null,
        public ?string $fuel = null,
        public ?string $transmission = null,
        public ?string $condition = null,
        public ?string $colour = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?int $minYear = null,
        public ?int $maxYear = null,
        public ?int $maxKm = null,
        public ?int $seats = null,
        public ?int $owners = null,
        public bool $featured = false,
        public string $sort = 'latest',
        public int $page = 1,
        public int $perPage = 12,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->query());
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $keyword = self::string($input['q'] ?? $input['keyword'] ?? null, 80);
        $minPrice = self::integer($input['min_price'] ?? null, 0);
        $maxPrice = self::integer($input['max_price'] ?? null, 0);

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $minYear = self::integer($input['min_year'] ?? null, 1980);
        $maxYear = self::integer($input['max_year'] ?? null, 1980);

        if ($minYear !== null && $maxYear !== null && $minYear > $maxYear) {
            [$minYear, $maxYear] = [$maxYear, $minYear];
        }

        $currentYear = (int) date('Y') + 1;
        $sort = (string) ($input['sort'] ?? 'latest');
        $perPage = self::integer($input['per_page'] ?? null, 1) ?? 12;

        return new self(
            keyword: $keyword,
            category: self::slug($input['category'] ?? null),
            brand: self::slug($input['brand'] ?? null),
            city: self::slug($input['city'] ?? null),
            fuel: self::slug($input['fuel'] ?? null),
            transmission: self::enumValue($input['transmission'] ?? null, array_keys(Transmission::options())),
            condition: self::enumValue($input['condition'] ?? null, array_keys(VehicleCondition::options())),
            colour: self::string($input['colour'] ?? null, 50),
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            minYear: $minYear !== null ? max(1980, min($minYear, $currentYear)) : null,
            maxYear: $maxYear !== null ? max(1980, min($maxYear, $currentYear)) : null,
            maxKm: self::integer($input['max_km'] ?? null, 0),
            seats: self::boundedInteger($input['seats'] ?? null, 2, 12),
            owners: self::boundedInteger($input['owners'] ?? null, 1, 5),
            featured: filter_var($input['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            sort: array_key_exists($sort, self::SORTS) ? $sort : 'latest',
            page: max(1, self::integer($input['page'] ?? null, 1) ?? 1),
            perPage: in_array($perPage, self::PER_PAGE, true)
                ? $perPage
                : (int) config('marketplace.pagination.listings', 12),
        );
    }

    public function hasActiveFilters(): bool
    {
        return (bool) array_filter($this->toQuery(), fn ($value, $key) => $key !== 'sort', ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Query-string values suitable for links and canonical detection.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'q' => $this->keyword,
            'category' => $this->category,
            'brand' => $this->brand,
            'city' => $this->city,
            'fuel' => $this->fuel,
            'transmission' => $this->transmission,
            'condition' => $this->condition,
            'colour' => $this->colour,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'min_year' => $this->minYear,
            'max_year' => $this->maxYear,
            'max_km' => $this->maxKm,
            'seats' => $this->seats,
            'owners' => $this->owners,
            'featured' => $this->featured ? 1 : null,
            'sort' => $this->sort !== 'latest' ? $this->sort : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function sortLabel(): string
    {
        return self::SORTS[$this->sort] ?? self::SORTS['latest'];
    }

    private static function string(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $maxLength, '');
    }

    private static function slug(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $slug = Str::slug($value);

        return $slug !== '' ? $slug : null;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private static function enumValue(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private static function integer(mixed $value, int $min): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max($min, (int) $value);
    }

    private static function boundedInteger(mixed $value, int $min, int $max): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max($min, min($max, (int) $value));
    }
}
