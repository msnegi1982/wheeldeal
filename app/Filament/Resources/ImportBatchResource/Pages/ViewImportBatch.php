<?php

namespace App\Filament\Resources\ImportBatchResource\Pages;

use App\Enums\ImportStatus;
use App\Filament\Resources\ImportBatchResource;
use App\Jobs\ProcessImportBatch;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewImportBatch extends ViewRecord
{
    protected static string $resource = ImportBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('report')
                ->label('Download error report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->record->error_report_path))
                ->action(fn (): ?StreamedResponse => ImportBatchResource::downloadReport($this->record)),
            Actions\Action::make('retry')
                ->label('Retry import')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ImportStatus::FAILED && $this->record->success_rows === 0)
                ->action(function () {
                    $this->record->errors()->delete();

                    $this->record->forceFill([
                        'status' => ImportStatus::PENDING,
                        'processed_rows' => 0,
                        'success_rows' => 0,
                        'error_rows' => 0,
                        'error_report_path' => null,
                        'started_at' => null,
                        'finished_at' => null,
                    ])->save();

                    ProcessImportBatch::dispatch($this->record);

                    $this->refreshFormData(['status', 'processed_rows', 'success_rows', 'error_rows']);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
