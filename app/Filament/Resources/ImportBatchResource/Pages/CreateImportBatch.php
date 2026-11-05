<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Enums\ImportStatus;
use App\Filament\Resources\ImportBatchResource;
use App\Jobs\ProcessImportBatch;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreateImportBatch extends CreateRecord
{
    protected static string $resource = ImportBatchResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('template')
                ->label('Download template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn (): StreamedResponse => ImportBatchResource::downloadTemplate()),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['disk'] = 'local';
        $data['status'] = ImportStatus::PENDING;
        $data['options'] = [
            'update_existing' => (bool) ($data['update_existing'] ?? false),
        ];

        if (blank($data['original_name'] ?? null)) {
            $data['original_name'] = basename((string) $data['stored_path']);
        }

        unset($data['update_existing']);

        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessImportBatch::dispatch($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return ImportBatchResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Import queued. Progress updates automatically.';
    }
}
