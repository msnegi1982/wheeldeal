<?php

namespace App\Repositories\Contracts;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface BrandRepositoryInterface
{
    public function activeWithCounts(): Collection;

    public function featured(int $limit): Collection;

    public function forCategory(Category $category): Collection;

    public function findBySlug(string $slug): ?Brand;
}
