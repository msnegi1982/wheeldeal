<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?string $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'old_path';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Redirect')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('old_path')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Path only, starting with a slash, e.g. /cars/old-slug.'),
                    Forms\Components\TextInput::make('new_path')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status_code')
                        ->options([301 => '301 — Permanent', 302 => '302 — Temporary'])
                        ->default(301)
                        ->required(),
                    Forms\Components\TextInput::make('hits')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\DateTimePicker::make('last_hit_at')
                        ->disabled()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('old_path')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('new_path')
                    ->searchable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('status_code')
                    ->badge()
                    ->color(fn (int $state) => $state === 301 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('hits')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_hit_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_code')
                    ->options([301 => '301', 302 => '302']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
