<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Filament\Resources\ImportBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListImportBatches extends ListRecords
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('template')
                ->label('Download template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn (): StreamedResponse => ImportBatchResource::downloadTemplate()),
            Actions\CreateAction::make()
                ->label('Upload vehicles'),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No vehicle imports yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Upload a CSV or Excel file to add vehicles in bulk. Every rejected row is reported with its reason.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-arrow-up-tray';
    }
}
