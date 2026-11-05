<?php

namespace App\Filament\Resources\FuelTypeResource\Pages;

use App\Filament\Resources\FuelTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFuelTypes extends ListRecords
{
    protected static string $resource = FuelTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
