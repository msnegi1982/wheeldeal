<?php

namespace App\Repositories\Eloquent;

use App\Models\Brand;
use App\Models\Category;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentBrandRepository implements BrandRepositoryInterface
{
    public function activeWithCounts(): Collection
    {
        return $this->baseQuery()->orderBy('sort_order')->orderBy('name')->get();
    }

    public function featured(int $limit): Collection
    {
        return $this->baseQuery()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function forCategory(Category $category): Collection
    {
        return $this->baseQuery()
            ->whereHas('vehicles', fn (Builder $query) => $query
                ->published()
                ->where('category_id', $category->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(string $slug): ?Brand
    {
        return $this->baseQuery()->where('slug', $slug)->first();
    }

    private function baseQuery(): Builder
    {
        return Brand::query()
            ->active()
            ->withCount(['vehicles as published_vehicles_count' => fn (Builder $query) => $query->published()])
            ->with('media');
    }
}
