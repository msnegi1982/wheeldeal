<?php

namespace App\Filament\Resources\VehicleVariantResource\Pages;

use App\Filament\Resources\VehicleVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleVariants extends ListRecords
{
    protected static string $resource = VehicleVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
