<?php

namespace App\Filament\Resources\EnquiryResource\Pages;

use App\Filament\Resources\EnquiryResource;
use App\Models\Enquiry;
use App\Support\CsvExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()?->can('export_enquiries'))
                ->action(fn (): StreamedResponse => CsvExporter::download(
                    'vehicle-enquiries-'.now()->format('Y-m-d').'.csv',
                    ['Received', 'Name', 'Phone', 'Email', 'Vehicle', 'Status', 'Message', 'Source'],
                    $this->getFilteredTableQuery()
                        ->with('vehicle')
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->map(fn (Enquiry $record) => [
                            $record->created_at?->format('Y-m-d H:i'),
                            $record->name,
                            $record->phone,
                            $record->email,
                            $record->vehicle?->title,
                            $record->status?->label(),
                            $record->message,
                            $record->source,
                        ])
                )),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No vehicle enquiries yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Enquiries from vehicle detail pages will appear here.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }
}
