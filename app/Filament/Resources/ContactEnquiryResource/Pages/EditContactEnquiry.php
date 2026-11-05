<?php

namespace App\Filament\Resources\ContactEnquiryResource\Pages;

use App\Filament\Resources\ContactEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactEnquiry extends EditRecord
{
    protected static string $resource = ContactEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
