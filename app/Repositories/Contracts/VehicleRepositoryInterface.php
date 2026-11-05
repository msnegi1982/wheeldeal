<?php

namespace App\Repositories\Contracts;

use App\DTOs\VehicleFilterData;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VehicleRepositoryInterface
{
    public function paginate(VehicleFilterData $filters): LengthAwarePaginator;

    public function findPublishedByUuid(string $uuid): ?Vehicle;

    public function findPublishedForRoute(string $brandSlug, string $modelSlug, string $vehicleSlug): ?Vehicle;

    public function featured(int $limit): Collection;

    public function latest(int $limit): Collection;

    public function popular(int $limit): Collection;

    public function similar(Vehicle $vehicle, int $limit): Collection;

    public function incrementViews(Vehicle $vehicle): void;

    public function publishedCount(): int;
}
