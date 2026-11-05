<?php

namespace App\Filament\Resources\CustomerRequestResource\Pages;

use App\Filament\Resources\CustomerRequestResource;
use App\Models\CustomerRequest;
use App\Support\CsvExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCustomerRequests extends ListRecords
{
    protected static string $resource = CustomerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()?->can('export_requests'))
                ->action(fn (): StreamedResponse => CsvExporter::download(
                    'sell-buy-requests-'.now()->format('Y-m-d').'.csv',
                    ['Received', 'Type', 'Status', 'Name', 'Phone', 'Email', 'City', 'Vehicle', 'Year', 'KM', 'Expected price', 'Verified at', 'Source'],
                    $this->getFilteredTableQuery()
                        ->with('city')
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->map(fn (CustomerRequest $record) => [
                            $record->created_at?->format('Y-m-d H:i'),
                            $record->type?->shortLabel(),
                            $record->status?->label(),
                            $record->name,
                            $record->phone,
                            $record->email,
                            $record->city?->name,
                            $record->vehicle_summary,
                            $record->year,
                            $record->km_driven,
                            $record->expected_price,
                            $record->otp_verified_at?->format('Y-m-d H:i'),
                            $record->source,
                        ])
                )),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No sell or buy requests yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Verified requests from the public forms will appear here.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-inbox';
    }
}
