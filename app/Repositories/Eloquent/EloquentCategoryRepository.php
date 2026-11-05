<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function activeWithCounts(): Collection
    {
        return Category::query()
            ->active()
            ->withCount(['vehicles as published_vehicles_count' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::query()
            ->active()
            ->withCount(['vehicles as published_vehicles_count' => fn (Builder $query) => $query->published()])
            ->where('slug', $slug)
            ->first();
    }
}
