<?php

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\EnquiryResource;
use App\Models\Enquiry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEnquiries extends TableWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest vehicle enquiries')
            ->query(Enquiry::query()->with('vehicle')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (Enquiry $record) => $record->vehicle?->title ?? 'Vehicle removed')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (EnquiryStatus $state) => $state->label())
                    ->color(fn (EnquiryStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->url(fn (Enquiry $record) => EnquiryResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_enquiries') ?? false;
    }
}
