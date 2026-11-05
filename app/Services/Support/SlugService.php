<?php

namespace App\Services\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Build a unique slug for a model, optionally scoped by parent columns
     * (for example vehicle slugs are unique per vehicle model).
     *
     * @param  array<string, mixed>  $scope
     */
    public function unique(
        string $value,
        string $modelClass,
        ?int $ignoreId = null,
        array $scope = [],
        string $column = 'slug'
    ): string {
        /** @var Model $model */
        $model = new $modelClass;
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while ($this->exists($model, $column, $slug, $ignoreId, $scope)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function exists(Model $model, string $column, string $slug, ?int $ignoreId, array $scope): bool
    {
        $query = $model->newQuery()->where($column, $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        foreach ($scope as $key => $value) {
            if ($value === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->exists();
    }
}
