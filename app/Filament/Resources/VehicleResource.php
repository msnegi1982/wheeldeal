<?php

namespace App\Filament\Resources;

use App\Enums\Transmission;
use App\Enums\VehicleCondition;
use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Vehicle')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make('Listing')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->required()
                                        ->maxLength(180)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state)))
                                        ->helperText('Shown in search results and the listing card.'),
                                    Forms\Components\TextInput::make('slug')
                                        ->required()
                                        ->maxLength(200)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Changing this creates a 301 redirect from the old URL.'),
                                    Forms\Components\Select::make('status')
                                        ->options(VehicleStatus::options())
                                        ->default(VehicleStatus::DRAFT->value)
                                        ->required()
                                        ->live()
                                        ->native(false),
                                    Forms\Components\DateTimePicker::make('published_at')
                                        ->label('Publish date')
                                        ->seconds(false)
                                        ->helperText('Publishing with no date sets it automatically.'),
                                    Forms\Components\Toggle::make('is_featured')
                                        ->inline(false),
                                    Forms\Components\Toggle::make('is_popular')
                                        ->inline(false),
                                    Forms\Components\DateTimePicker::make('sold_at')
                                        ->label('Sold date')
                                        ->seconds(false)
                                        ->visible(fn (Get $get) => $get('status') === VehicleStatus::SOLD->value),
                                    Forms\Components\TextInput::make('popularity_score')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Higher scores appear first in “Most popular”.'),
                                ]),

                            Forms\Components\Section::make('Vehicle')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('brand_id')
                                        ->label('Brand')
                                        ->relationship('brand', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('vehicle_model_id', null)),
                                    Forms\Components\Select::make('vehicle_model_id')
                                        ->label('Model')
                                        ->options(fn (Get $get) => VehicleModel::query()
                                            ->where('brand_id', $get('brand_id'))
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('vehicle_variant_id', null)),
                                    Forms\Components\Select::make('vehicle_variant_id')
                                        ->label('Variant')
                                        ->options(fn (Get $get) => VehicleModel::query()
                                            ->find($get('vehicle_model_id'))
                                            ?->variants()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all() ?? [])
                                        ->searchable(),
                                    Forms\Components\Select::make('category_id')
                                        ->label('Body type')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Forms\Components\Select::make('fuel_type_id')
                                        ->label('Fuel type')
                                        ->relationship('fuelType', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Forms\Components\Select::make('city_id')
                                        ->label('City')
                                        ->relationship('city', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Forms\Components\Select::make('transmission')
                                        ->options(Transmission::options())
                                        ->required()
                                        ->native(false),
                                    Forms\Components\Select::make('condition')
                                        ->options(VehicleCondition::options())
                                        ->native(false),
                                    Forms\Components\TextInput::make('colour')
                                        ->maxLength(50),
                                    Forms\Components\TextInput::make('year')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1980)
                                        ->maxValue((int) date('Y') + 1),
                                    Forms\Components\TextInput::make('price')
                                        ->numeric()
                                        ->required()
                                        ->prefix('₹')
                                        ->helperText('Full asking price in INR, no decimals.'),
                                    Forms\Components\TextInput::make('original_price')
                                        ->numeric()
                                        ->prefix('₹')
                                        ->helperText('Optional showroom/new price for comparison.'),
                                    Forms\Components\TextInput::make('km_driven')
                                        ->numeric()
                                        ->required()
                                        ->suffix('km'),
                                    Forms\Components\TextInput::make('owner_count')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->maxValue(10),
                                    Forms\Components\TextInput::make('seats')
                                        ->numeric()
                                        ->minValue(2)
                                        ->maxValue(12),
                                    Forms\Components\TextInput::make('registration_state')
                                        ->maxLength(80),
                                    Forms\Components\TextInput::make('registration_year')
                                        ->numeric()
                                        ->minValue(1980)
                                        ->maxValue((int) date('Y') + 1),
                                    Forms\Components\Textarea::make('description')
                                        ->rows(6)
                                        ->helperText('Aim for 80–150 words covering condition, service history and why the car is worth a look.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Specifications')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->schema([
                            Forms\Components\Section::make('Engine and performance')
                                ->columns(4)
                                ->schema([
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
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Features')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Forms\Components\Section::make('Equipment')
                                ->description('Features are grouped by their category on the public listing.')
                                ->schema([
                                    Forms\Components\Select::make('features')
                                        ->relationship('features', 'name')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Select every feature this car has.'),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Media')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Section::make('Gallery')
                                ->description('Upload at least 6 images. The first image becomes the card thumbnail and Open Graph image.')
                                ->schema([
                                    Forms\Components\SpatieMediaLibraryFileUpload::make('gallery')
                                        ->collection('gallery')
                                        ->image()
                                        ->multiple()
                                        ->reorderable()
                                        ->maxFiles((int) config('marketplace.uploads.max_gallery_images', 12))
                                        ->maxSize((int) config('marketplace.uploads.max_image_kb', 5120))
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->helperText('Automatic WebP thumb/card/large conversions are generated in the queue.'),
                                ]),
                            Forms\Components\Section::make('360° frames')
                                ->description('Upload 24–36 frames in clockwise order. Frames are ordered by upload sequence.')
                                ->schema([
                                    Forms\Components\SpatieMediaLibraryFileUpload::make('frames_360')
                                        ->collection('360-frames')
                                        ->label('Frames')
                                        ->image()
                                        ->multiple()
                                        ->reorderable()
                                        ->maxFiles((int) config('marketplace.uploads.max_360_frames', 36))
                                        ->maxSize((int) config('marketplace.uploads.max_image_kb', 5120))
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->helperText('The viewer activates automatically when at least 24 frames are present.'),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Forms\Components\Section::make('Search preview')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('meta_title')
                                        ->maxLength(255)
                                        ->helperText('Defaults to “{year} {brand} {model} — ₹price | WheelDeal”.'),
                                    Forms\Components\TextInput::make('meta_description')
                                        ->maxLength(255)
                                        ->helperText('Aim for 140–158 characters.'),
                                    Forms\Components\TextInput::make('og_image')
                                        ->label('Open Graph image URL')
                                        ->url()
                                        ->maxLength(255)
                                        ->helperText('Optional. The primary gallery image is used by default.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Listing')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('title')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (VehicleStatus $state) => $state->label())
                        ->color(fn (VehicleStatus $state) => $state->color()),
                    Infolists\Components\TextEntry::make('brand.name')->label('Brand'),
                    Infolists\Components\TextEntry::make('vehicleModel.name')->label('Model'),
                    Infolists\Components\TextEntry::make('variant.name')->label('Variant')->placeholder('—'),
                    Infolists\Components\TextEntry::make('year'),
                    Infolists\Components\TextEntry::make('price')
                        ->money('INR'),
                    Infolists\Components\TextEntry::make('km_driven')
                        ->label('Kilometres')
                        ->formatStateUsing(fn ($state) => number_format((int) $state).' km'),
                    Infolists\Components\TextEntry::make('owner_label')->label('Ownership'),
                    Infolists\Components\TextEntry::make('city.name')->label('City'),
                    Infolists\Components\TextEntry::make('fuelType.name')->label('Fuel'),
                    Infolists\Components\TextEntry::make('transmission')
                        ->formatStateUsing(fn (?Transmission $state) => $state?->label() ?? '—'),
                    Infolists\Components\TextEntry::make('condition')
                        ->formatStateUsing(fn (?VehicleCondition $state) => $state?->label() ?? '—'),
                    Infolists\Components\TextEntry::make('published_at')
                        ->dateTime('d M Y')
                        ->placeholder('Unpublished'),
                    Infolists\Components\TextEntry::make('description')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),
            Infolists\Components\Section::make('Gallery')
                ->schema([
                    Infolists\Components\SpatieMediaLibraryImageEntry::make('gallery')
                        ->collection('gallery')
                        ->conversion('card')
                        ->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('SEO')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('meta_title')->placeholder('Automatic'),
                    Infolists\Components\TextEntry::make('meta_description')->placeholder('Automatic'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('gallery')
                    ->label('')
                    ->collection('gallery')
                    ->conversion('card')
                    ->height(52)
                    ->width(72),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Vehicle $record) => $record->brand?->name.' · '.$record->vehicleModel?->name)
                    ->wrap(),
                Tables\Columns\TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (VehicleStatus $state) => $state->label())
                    ->color(fn (VehicleStatus $state) => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('km_driven')
                    ->label('Km')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('Unpublished')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(VehicleStatus::options())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('fuel_type_id')
                    ->label('Fuel')
                    ->relationship('fuelType', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\TextInput::make('price_from')->numeric()->prefix('₹'),
                        Forms\Components\TextInput::make('price_to')->numeric()->prefix('₹'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_from'] ?? null, fn (Builder $q, $value) => $q->where('price', '>=', $value))
                            ->when($data['price_to'] ?? null, fn (Builder $q, $value) => $q->where('price', '<=', $value));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Vehicle $record) => $record->status !== VehicleStatus::PUBLISHED)
                    ->requiresConfirmation()
                    ->action(fn (Vehicle $record) => $record->update([
                        'status' => VehicleStatus::PUBLISHED,
                        'published_at' => $record->published_at ?? now(),
                    ])),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', VehicleStatus::DRAFT->value)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
