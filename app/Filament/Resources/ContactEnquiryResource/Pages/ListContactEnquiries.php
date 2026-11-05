<?php

namespace App\Filament\Resources\ContactEnquiryResource\Pages;

use App\Filament\Resources\ContactEnquiryResource;
use App\Models\ContactEnquiry;
use App\Support\CsvExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListContactEnquiries extends ListRecords
{
    protected static string $resource = ContactEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()?->can('export_contact_enquiries'))
                ->action(fn (): StreamedResponse => CsvExporter::download(
                    'contact-messages-'.now()->format('Y-m-d').'.csv',
                    ['Received', 'Name', 'Phone', 'Email', 'Subject', 'Message', 'Status'],
                    $this->getFilteredTableQuery()
                        ->orderByDesc('created_at')
                        ->cursor()
                        ->map(fn (ContactEnquiry $record) => [
                            $record->created_at?->format('Y-m-d H:i'),
                            $record->name,
                            $record->phone,
                            $record->email,
                            $record->subject,
                            $record->message,
                            $record->status?->label(),
                        ])
                )),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No contact messages yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Messages sent from the contact page will appear here.';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-envelope';
    }
}
