<?php

namespace App\Repositories\Eloquent;

use App\DTOs\VehicleFilterData;
use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Columns needed by cards and JSON resources. Selecting explicit columns
     * keeps list queries light while still satisfying accessors.
     *
     * @var array<int, string>
     */
    private const LISTING_RELATIONS = [
        'brand:id,name,slug,is_active',
        'vehicleModel:id,brand_id,name,slug,is_active',
        'variant:id,vehicle_model_id,name,slug',
        'category:id,name,slug,is_active',
        'fuelType:id,name,slug,is_active',
        'city:id,name,slug,is_active',
    ];

    /**
     * @var array<int, string>
     */
    private const DETAIL_RELATIONS = [
        'brand:id,name,slug,short_description,description,is_active',
        'vehicleModel:id,brand_id,category_id,name,slug,description,is_active',
        'variant:id,vehicle_model_id,fuel_type_id,name,slug,transmission,engine_cc,power_bhp,torque_nm,mileage_kmpl,seats',
        'category:id,name,slug,description,is_active',
        'fuelType:id,name,slug,is_active',
        'city:id,name,slug,state,is_active',
        'features',
        'media',
    ];

    public function paginate(VehicleFilterData $filters): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->paginate($filters->perPage, ['vehicles.*'], 'page', $filters->page)
            ->withQueryString();
    }

    public function findPublishedByUuid(string $uuid): ?Vehicle
    {
        return $this->publishableQuery()
            ->with(self::DETAIL_RELATIONS)
            ->where('uuid', $uuid)
            ->first();
    }

    public function findPublishedForRoute(string $brandSlug, string $modelSlug, string $vehicleSlug): ?Vehicle
    {
        return $this->publishableQuery()
            ->with(self::DETAIL_RELATIONS)
            ->where('slug', $vehicleSlug)
            ->whereHas('brand', fn (Builder $query) => $query->where('slug', $brandSlug)->where('is_active', true))
            ->whereHas('vehicleModel', fn (Builder $query) => $query->where('slug', $modelSlug)->where('is_active', true))
            ->first();
    }

    public function featured(int $limit): Collection
    {
        return $this->withListingRelations($this->publishableQuery())
            ->featured()
            ->orderByDesc('popularity_score')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function latest(int $limit): Collection
    {
        return $this->withListingRelations($this->publishableQuery())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function popular(int $limit): Collection
    {
        return $this->withListingRelations($this->publishableQuery())
            ->popular()
            ->limit($limit)
            ->get();
    }

    public function similar(Vehicle $vehicle, int $limit): Collection
    {
        return $this->withListingRelations($this->publishableQuery())
            ->similarTo($vehicle)
            ->orderByDesc('popularity_score')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function incrementViews(Vehicle $vehicle): void
    {
        Vehicle::query()->whereKey($vehicle->getKey())->increment('views_count');
        $vehicle->views_count = (int) $vehicle->views_count + 1;
    }

    public function publishedCount(): int
    {
        return Vehicle::query()->published()->count();
    }

    private function publishableQuery(): Builder
    {
        return Vehicle::query()
            ->published()
            ->withCount(['media as frames_360_count' => fn (Builder $query) => $query->where('collection_name', '360-frames')])
            ->whereHas('brand', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('vehicleModel', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('fuelType', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('city', fn (Builder $query) => $query->where('is_active', true));
    }

    private function withListingRelations(Builder $query): Builder
    {
        return $query
            ->with(self::LISTING_RELATIONS)
            ->with(['media' => fn ($mediaQuery) => $mediaQuery->where('collection_name', 'gallery')]);
    }

    private function filteredQuery(VehicleFilterData $filters): Builder
    {
        $query = $this->withListingRelations($this->publishableQuery());

        if ($filters->keyword) {
            $like = '%'.addcslashes($filters->keyword, '%_\\').'%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('colour', 'like', $like)
                    ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('vehicleModel', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('variant', fn (Builder $query) => $query->where('name', 'like', $like));
            });
        }

        if ($filters->category) {
            $query->whereHas('category', fn (Builder $query) => $query->where('slug', $filters->category));
        }

        if ($filters->brand) {
            $query->whereHas('brand', fn (Builder $query) => $query->where('slug', $filters->brand));
        }

        if ($filters->city) {
            $query->whereHas('city', fn (Builder $query) => $query->where('slug', $filters->city));
        }

        if ($filters->fuel) {
            $query->whereHas('fuelType', fn (Builder $query) => $query->where('slug', $filters->fuel));
        }

        if ($filters->transmission) {
            $query->where('transmission', $filters->transmission);
        }

        if ($filters->condition) {
            $query->where('condition', $filters->condition);
        }

        if ($filters->colour) {
            $query->where('colour', 'like', $filters->colour);
        }

        if ($filters->minPrice !== null) {
            $query->where('price', '>=', $filters->minPrice);
        }

        if ($filters->maxPrice !== null) {
            $query->where('price', '<=', $filters->maxPrice);
        }

        if ($filters->minYear !== null) {
            $query->where('year', '>=', $filters->minYear);
        }

        if ($filters->maxYear !== null) {
            $query->where('year', '<=', $filters->maxYear);
        }

        if ($filters->maxKm !== null) {
            $query->where('km_driven', '<=', $filters->maxKm);
        }

        if ($filters->seats !== null) {
            $query->where('seats', '>=', $filters->seats);
        }

        if ($filters->owners !== null) {
            $query->where('owner_count', '<=', $filters->owners);
        }

        if ($filters->featured) {
            $query->where('is_featured', true);
        }

        return match ($filters->sort) {
            'price_low' => $query->orderBy('price')->orderByDesc('published_at'),
            'price_high' => $query->orderByDesc('price')->orderByDesc('published_at'),
            'year_new' => $query->orderByDesc('year')->orderByDesc('published_at'),
            'km_low' => $query->orderBy('km_driven')->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('popularity_score')->orderByDesc('views_count'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }
}
