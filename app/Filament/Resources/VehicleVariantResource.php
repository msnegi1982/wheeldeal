<?php

namespace App\Filament\Resources;

use App\Enums\Transmission;
use App\Filament\Resources\VehicleVariantResource\Pages;
use App\Models\VehicleModel;
use App\Models\VehicleVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VehicleVariantResource extends Resource
{
    protected static ?string $model = VehicleVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Variants';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Variant')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('vehicle_model_id')
                        ->label('Model')
                        ->relationship('vehicleModel', 'name')
                        ->getOptionLabelFromRecordUsing(fn (VehicleModel $record) => $record->display_name)
                        ->searchable(['name'])
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                            $model = VehicleModel::query()->with('brand')->find($get('vehicle_model_id'));
                            $set('slug', Str::slug(trim(($model?->display_name ?? '').' '.$state)));
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(180)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('fuel_type_id')
                        ->label('Fuel type')
                        ->relationship('fuelType', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('transmission')
                        ->options(Transmission::options())
                        ->native(false),
                    Forms\Components\TextInput::make('engine_cc')
                        ->numeric()
                        ->suffix('cc'),
                    Forms\Components\TextInput::make('power_bhp')
                        ->numeric()
                        ->suffix('bhp'),
                    Forms\Components\TextInput::make('torque_nm')
                        ->numeric()
                        ->suffix('Nm'),
                    Forms\Components\TextInput::make('mileage_kmpl')
                        ->numeric()
                        ->suffix('km/l'),
                    Forms\Components\TextInput::make('seats')
                        ->numeric()
                        ->minValue(2)
                        ->maxValue(12),
                    Forms\Components\TextInput::make('price_min')
                        ->numeric()
                        ->prefix('₹'),
                    Forms\Components\TextInput::make('price_max')
                        ->numeric()
                        ->prefix('₹'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicleModel.brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicleModel.name')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fuelType.name')
                    ->label('Fuel')
                    ->badge(),
                Tables\Columns\TextColumn::make('transmission')
                    ->badge(),
                Tables\Columns\TextColumn::make('price_min')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_model_id')
                    ->label('Model')
                    ->relationship('vehicleModel', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('fuel_type_id')
                    ->label('Fuel')
                    ->relationship('fuelType', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
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
            ->defaultSort('vehicle_model_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleVariants::route('/'),
            'create' => Pages\CreateVehicleVariant::route('/create'),
            'edit' => Pages\EditVehicleVariant::route('/{record}/edit'),
        ];
    }
}
