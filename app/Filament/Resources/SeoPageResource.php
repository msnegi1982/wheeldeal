<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoPageResource\Pages;
use App\Models\SeoPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SeoPageResource extends Resource
{
    protected static ?string $model = SeoPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Landing pages';

    protected static ?string $recordTitleAttribute = 'h1';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Target page')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Body type')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand (optional)')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Leave blank for the category page, or choose a brand for /cars/{category}/{brand}.'),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(160)
                        ->unique(ignoreRecord: true)
                        ->helperText('Internal identifier; the public URL is built from the category and brand.'),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ]),
            Forms\Components\Section::make('Copy and metadata')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('h1')
                        ->required()
                        ->maxLength(160)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                    Forms\Components\TextInput::make('meta_title')
                        ->maxLength(255)
                        ->helperText('Defaults to “Used {brand} {category} cars for sale”.'),
                    Forms\Components\TextInput::make('meta_description')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('intro_copy')
                        ->rows(6)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('h1')
                    ->label('H1')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SeoPage $record) => route('categories.show', $record->category?->slug ?? '')),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Body type')
                    ->badge(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder('All brands'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name'),
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
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoPages::route('/'),
            'create' => Pages\CreateSeoPage::route('/create'),
            'edit' => Pages\EditSeoPage::route('/{record}/edit'),
        ];
    }
}
