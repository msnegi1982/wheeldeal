<?php

namespace App\Filament\Resources\EnquiryResource\Pages;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\EnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEnquiry extends ViewRecord
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('contacted')
                ->label('Mark contacted')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->visible(fn () => $this->record->status === EnquiryStatus::NEW)
                ->action(function (): void {
                    $this->record->update(['status' => EnquiryStatus::CONTACTED]);
                    $this->record->refresh();
                }),
            Actions\Action::make('close')
                ->label('Close')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status !== EnquiryStatus::CLOSED)
                ->action(function (): void {
                    $this->record->update(['status' => EnquiryStatus::CLOSED]);
                    $this->record->refresh();
                }),
            Actions\EditAction::make(),
        ];
    }
}
