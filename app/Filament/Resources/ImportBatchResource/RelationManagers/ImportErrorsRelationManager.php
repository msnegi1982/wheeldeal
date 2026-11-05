<?php

namespace App\Filament\Resources\ImportBatchResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImportErrorsRelationManager extends RelationManager
{
    protected static string $relationship = 'errors';

    protected static ?string $title = 'Rejected rows';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('Row')
                    ->width(70)
                    ->sortable(),
                Tables\Columns\TextColumn::make('column')
                    ->label('Column')
                    ->badge()
                    ->placeholder('Row')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('message')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('row_data')
                    ->label('Row data')
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? collect($state)->map(fn ($value, $key) => $key.': '.$value)->implode(' · ')
                        : (string) $state)
                    ->limit(120)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('row_number')
            ->paginated([25, 50, 100]);
    }
}
