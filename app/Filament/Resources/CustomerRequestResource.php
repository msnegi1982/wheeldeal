<?php

namespace App\Filament\Resources;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Resources\CustomerRequestResource\Pages;
use App\Filament\Resources\CustomerRequestResource\RelationManagers\NotesRelationManager;
use App\Models\CustomerRequest;
use App\Models\User;
use App\Services\Requests\CustomerRequestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerRequestResource extends Resource
{
    protected static ?string $model = CustomerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Sell / buy requests';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Workflow')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(RequestStatus::options())
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assigned to')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->placeholder('Unassigned'),
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Internal summary')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Contact')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->disabled(),
                    Forms\Components\TextInput::make('phone')->disabled(),
                    Forms\Components\TextInput::make('email')->disabled(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Lead')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('type')
                        ->badge()
                        ->formatStateUsing(fn (RequestType $state) => $state->label())
                        ->color(fn (RequestType $state) => $state === RequestType::SELL ? 'warning' : 'info'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (RequestStatus $state) => $state->label())
                        ->color(fn (RequestStatus $state) => $state->color()),
                    Infolists\Components\TextEntry::make('otp_verified_at')
                        ->label('OTP verified')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Pending'),
                    Infolists\Components\TextEntry::make('name'),
                    Infolists\Components\TextEntry::make('phone')->copyable(),
                    Infolists\Components\TextEntry::make('email')->placeholder('—'),
                    Infolists\Components\TextEntry::make('city.name')->label('City')->placeholder('—'),
                    Infolists\Components\TextEntry::make('source')->badge(),
                    Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y H:i'),
                ]),
            Infolists\Components\Section::make('Vehicle')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('vehicle_summary')->label('Vehicle')->placeholder('Not specified'),
                    Infolists\Components\TextEntry::make('km_driven')
                        ->label('Kilometres')
                        ->formatStateUsing(fn ($state) => $state ? number_format((int) $state).' km' : '—'),
                    Infolists\Components\TextEntry::make('expected_price')
                        ->label('Expected price')
                        ->formatStateUsing(fn ($state) => $state ? format_inr((int) $state) : '—'),
                    Infolists\Components\TextEntry::make('condition')->placeholder('—'),
                    Infolists\Components\TextEntry::make('colour')->placeholder('—'),
                    Infolists\Components\TextEntry::make('message')->columnSpanFull()->placeholder('—'),
                ]),
            Infolists\Components\Section::make('Internal')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Unassigned'),
                    Infolists\Components\TextEntry::make('admin_notes')->columnSpanFull()->placeholder('—'),
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
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (RequestType $state) => $state->shortLabel())
                    ->color(fn (RequestType $state) => $state === RequestType::SELL ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->description(fn (CustomerRequest $record) => $record->phone),
                Tables\Columns\TextColumn::make('vehicle_summary')
                    ->label('Vehicle')
                    ->placeholder('Not specified')
                    ->wrap(),
                Tables\Columns\TextColumn::make('expected_price')
                    ->label('Expected')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RequestStatus $state) => $state->label())
                    ->color(fn (RequestStatus $state) => $state->color())
                    ->sortable(),
                Tables\Columns\IconColumn::make('otp_verified_at')
                    ->label('OTP')
                    ->boolean()
                    ->getStateUsing(fn (CustomerRequest $record) => $record->isVerified()),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Owner')
                    ->placeholder('Unassigned')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(RequestStatus::options())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(RequestType::options()),
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn ($query) => $query->whereNotNull('otp_verified_at')),
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
                    ->visible(fn (CustomerRequest $record) => $record->status === RequestStatus::NEW)
                    ->action(fn (CustomerRequest $record) => app(CustomerRequestService::class)
                        ->updateStatus($record, RequestStatus::CONTACTED, auth()->user())),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CustomerRequest $record) => in_array($record->status, [RequestStatus::NEW, RequestStatus::CONTACTED], true))
                    ->action(fn (CustomerRequest $record) => app(CustomerRequestService::class)
                        ->updateStatus($record, RequestStatus::APPROVED, auth()->user())),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (CustomerRequest $record) => ! in_array($record->status, [RequestStatus::APPROVED, RequestStatus::REJECTED], true))
                    ->requiresConfirmation()
                    ->action(fn (CustomerRequest $record) => app(CustomerRequestService::class)
                        ->updateStatus($record, RequestStatus::REJECTED, auth()->user())),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markContacted')
                        ->label('Mark contacted')
                        ->icon('heroicon-o-phone')
                        ->action(fn ($records) => $records->each(fn (CustomerRequest $record) => app(CustomerRequestService::class)
                            ->updateStatus($record, RequestStatus::CONTACTED, auth()->user()))),
                    Tables\Actions\BulkAction::make('markApproved')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn (CustomerRequest $record) => app(CustomerRequestService::class)
                            ->updateStatus($record, RequestStatus::APPROVED, auth()->user()))),
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
        $count = static::getModel()::query()->where('status', RequestStatus::NEW->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerRequests::route('/'),
            'view' => Pages\ViewCustomerRequest::route('/{record}'),
            'edit' => Pages\EditCustomerRequest::route('/{record}/edit'),
        ];
    }
}
