<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;

class SlugRedirectObserver
{
    public function updated(Model $model): void
    {
        if (! $model->wasChanged('slug')) {
            return;
        }

        $oldSlug = (string) $model->getOriginal('slug');

        match (true) {
            $model instanceof Vehicle => $this->vehicleChanged($model, $oldSlug),
            $model instanceof Brand => $this->brandChanged($model, $oldSlug),
            $model instanceof Category => $this->categoryChanged($model, $oldSlug),
            $model instanceof VehicleModel => $this->modelChanged($model, $oldSlug),
            default => null,
        };
    }

    private function vehicleChanged(Vehicle $vehicle, string $oldSlug): void
    {
        if (! $vehicle->brand || ! $vehicle->vehicleModel) {
            return;
        }

        $this->redirect(
            '/cars/'.$vehicle->brand->slug.'/'.$vehicle->vehicleModel->slug.'/'.$oldSlug,
            $vehicle->canonical_path,
            $vehicle
        );
    }

    private function brandChanged(Brand $brand, string $oldSlug): void
    {
        $this->redirect('/cars/brand/'.$oldSlug, $brand->canonical_path, $brand);

        $brand->vehicles()->with('vehicleModel')->chunkById(500, function ($vehicles) use ($oldSlug) {
            foreach ($vehicles as $vehicle) {
                if (! $vehicle->vehicleModel) {
                    continue;
                }

                $this->redirect(
                    '/cars/'.$oldSlug.'/'.$vehicle->vehicleModel->slug.'/'.$vehicle->slug,
                    $vehicle->canonical_path,
                    $vehicle
                );
            }
        });
    }

    private function categoryChanged(Category $category, string $oldSlug): void
    {
        $this->redirect('/cars/'.$oldSlug, $category->canonical_path, $category);

        Brand::query()
            ->whereHas('vehicles', fn ($query) => $query->where('category_id', $category->id))
            ->each(function (Brand $brand) use ($oldSlug, $category) {
                $this->redirect(
                    '/cars/'.$oldSlug.'/'.$brand->slug,
                    '/cars/'.$category->slug.'/'.$brand->slug,
                    $category
                );
            });
    }

    private function modelChanged(VehicleModel $model, string $oldSlug): void
    {
        $brandSlug = $model->brand?->slug;

        if (! $brandSlug) {
            return;
        }

        $model->vehicles()->each(function (Vehicle $vehicle) use ($oldSlug, $brandSlug) {
            $this->redirect(
                '/cars/'.$brandSlug.'/'.$oldSlug.'/'.$vehicle->slug,
                $vehicle->canonical_path,
                $vehicle
            );
        });
    }

    private function redirect(string $oldPath, ?string $newPath, Model $subject): void
    {
        $oldPath = '/'.ltrim($oldPath, '/');
        $newPath = $newPath ? '/'.ltrim($newPath, '/') : null;

        if (! $newPath || $oldPath === $newPath) {
            return;
        }

        Redirect::updateOrCreate(
            ['old_path' => $oldPath],
            [
                'new_path' => $newPath,
                'model_type' => $subject::class,
                'model_id' => $subject->getKey(),
                'status_code' => 301,
            ]
        );
    }
}
