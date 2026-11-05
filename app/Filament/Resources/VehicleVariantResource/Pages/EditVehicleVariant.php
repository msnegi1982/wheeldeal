<?php

namespace App\Filament\Resources\VehicleVariantResource\Pages;

use App\Filament\Resources\VehicleVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleVariant extends EditRecord
{
    protected static string $resource = VehicleVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
