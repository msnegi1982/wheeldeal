<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function activeWithCounts(): Collection;

    public function findBySlug(string $slug): ?Category;
}
