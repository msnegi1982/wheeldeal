<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Filament\Resources\CustomerRequestResource;
use App\Models\CustomerRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRequests extends TableWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest sell / buy requests')
            ->query(
                CustomerRequest::query()
                    ->with('city')
                    ->whereIn('status', array_keys(RequestStatus::options()))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (CustomerRequest $record) => $record->type?->shortLabel().' · '.($record->city?->name ?? '—'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_summary')
                    ->label('Vehicle')
                    ->limit(30)
                    ->placeholder('Any vehicle')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RequestStatus $state) => $state->label())
                    ->color(fn (RequestStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->url(fn (CustomerRequest $record) => CustomerRequestResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_any_requests') ?? false;
    }
}
