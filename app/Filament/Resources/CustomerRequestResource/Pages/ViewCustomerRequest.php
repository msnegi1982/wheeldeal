<?php

namespace App\Filament\Resources\CustomerRequestResource\Pages;

use App\Enums\RequestStatus;
use App\Filament\Resources\CustomerRequestResource;
use App\Services\Requests\CustomerRequestService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerRequest extends ViewRecord
{
    protected static string $resource = CustomerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('contacted')
                ->label('Mark contacted')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->visible(fn () => $this->record->status === RequestStatus::NEW)
                ->action(function (): void {
                    app(CustomerRequestService::class)
                        ->updateStatus($this->record, RequestStatus::CONTACTED, auth()->user());
                    $this->record->refresh();
                }),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [RequestStatus::NEW, RequestStatus::CONTACTED], true))
                ->action(function (): void {
                    app(CustomerRequestService::class)
                        ->updateStatus($this->record, RequestStatus::APPROVED, auth()->user());
                    $this->record->refresh();
                }),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => ! in_array($this->record->status, [RequestStatus::APPROVED, RequestStatus::REJECTED], true))
                ->action(function (): void {
                    app(CustomerRequestService::class)
                        ->updateStatus($this->record, RequestStatus::REJECTED, auth()->user());
                    $this->record->refresh();
                }),
            Actions\EditAction::make(),
        ];
    }
}
