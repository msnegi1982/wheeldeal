<?php

namespace App\Filament\Resources;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\ContactEnquiryResource\Pages;
use App\Filament\Resources\ContactEnquiryResource\RelationManagers\NotesRelationManager;
use App\Models\ContactEnquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactEnquiryResource extends Resource
{
    protected static ?string $model = ContactEnquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Contact messages';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Follow-up')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(EnquiryStatus::options())
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('admin_notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Message')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (EnquiryStatus $state) => $state->label())
                        ->color(fn (EnquiryStatus $state) => $state->color()),
                    Infolists\Components\TextEntry::make('name'),
                    Infolists\Components\TextEntry::make('phone')->copyable(),
                    Infolists\Components\TextEntry::make('email')->placeholder('—'),
                    Infolists\Components\TextEntry::make('subject')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('message')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y H:i'),
                ]),
            Infolists\Components\Section::make('Internal')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_notes')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->description(fn (ContactEnquiry $record) => $record->phone),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('message')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (EnquiryStatus $state) => $state->label())
                    ->color(fn (EnquiryStatus $state) => $state->color())
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EnquiryStatus::options())
                    ->multiple(),
                Tables\Filters\Filter::make('created')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('contacted')
                    ->label('Mark contacted')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->visible(fn (ContactEnquiry $record) => $record->status === EnquiryStatus::NEW)
                    ->action(fn (ContactEnquiry $record) => $record->update(['status' => EnquiryStatus::CONTACTED])),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ContactEnquiry $record) => $record->status !== EnquiryStatus::CLOSED)
                    ->requiresConfirmation()
                    ->action(fn (ContactEnquiry $record) => $record->update(['status' => EnquiryStatus::CLOSED])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', EnquiryStatus::NEW->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactEnquiries::route('/'),
            'view' => Pages\ViewContactEnquiry::route('/{record}'),
            'edit' => Pages\EditContactEnquiry::route('/{record}/edit'),
        ];
    }
}
