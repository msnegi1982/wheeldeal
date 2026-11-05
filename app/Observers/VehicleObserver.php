<?php

namespace App\Observers;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;

class VehicleObserver
{
    public function saving(Vehicle $vehicle): void
    {
        if ($vehicle->status === VehicleStatus::PUBLISHED && ! $vehicle->published_at) {
            $vehicle->published_at = now();
        }

        if ($vehicle->status === VehicleStatus::SOLD && ! $vehicle->sold_at) {
            $vehicle->sold_at = now();
        }
    }
}
