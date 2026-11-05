<?php

namespace App\Filament\Resources;

use App\Enums\ImportStatus;
use App\Filament\Resources\ImportBatchResource\Pages;
use App\Filament\Resources\ImportBatchResource\RelationManagers\ImportErrorsRelationManager;
use App\Jobs\ProcessImportBatch;
use App\Models\ImportBatch;
use App\Support\VehicleImportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportBatchResource extends Resource
{
    protected static ?string $model = ImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Vehicle imports';

    protected static ?string $modelLabel = 'vehicle import';

    protected static ?string $pluralModelLabel = 'vehicle imports';

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Spreadsheet')
                ->description('Upload a CSV, XLSX or XLS file. The first row must contain column headers.')
                ->schema([
                    Forms\Components\Hidden::make('original_name'),
                    Forms\Components\FileUpload::make('stored_path')
                        ->label('Vehicle file')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(10240)
                        ->required()
                        ->storeFileNamesIn('original_name')
                        ->helperText('Maximum 10 MB. Download the template below for the expected columns.'),
                    Forms\Components\Toggle::make('update_existing')
                        ->label('Update vehicles that already use the supplied slug')
                        ->helperText('Leave off to report a row error when a slug already exists. Matching still requires the model to be correct.')
                        ->default(false),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Import')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('original_name')
                        ->label('File')
                        ->columnSpan(2),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (ImportStatus $state) => $state->label())
                        ->color(fn (ImportStatus $state) => $state->color()),
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Uploaded by')
                        ->placeholder('System'),
                    Infolists\Components\TextEntry::make('progress')
                        ->label('Progress')
                        ->formatStateUsing(fn (int $state, ImportBatch $record) => $record->processed_rows.' / '.$record->total_rows.' rows ('.$state.'%)'),
                    Infolists\Components\TextEntry::make('success_rows')
                        ->label('Imported'),
                    Infolists\Components\TextEntry::make('error_rows')
                        ->label('Rows with errors')
                        ->color(fn (ImportBatch $record) => $record->error_rows > 0 ? 'danger' : 'success'),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Uploaded')
                        ->dateTime('d M Y, H:i'),
                    Infolists\Components\TextEntry::make('started_at')
                        ->label('Started')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Not started'),
                    Infolists\Components\TextEntry::make('finished_at')
                        ->label('Finished')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('In progress'),
                    Infolists\Components\TextEntry::make('options.update_existing')
                        ->label('Update existing')
                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('File')
                    ->searchable()
                    ->wrap()
                    ->description(fn (ImportBatch $record) => $record->created_at?->format('d M Y, H:i')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ImportStatus $state) => $state->label())
                    ->color(fn (ImportStatus $state) => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->formatStateUsing(fn (int $state, ImportBatch $record) => $record->processed_rows.' / '.$record->total_rows.' ('.$state.'%)')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('success_rows')
                    ->label('Imported')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_rows')
                    ->label('Errors')
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ImportStatus::options())
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('report')
                    ->label('Error report')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn (ImportBatch $record) => filled($record->error_report_path))
                    ->action(fn (ImportBatch $record): ?StreamedResponse => static::downloadReport($record)),
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Rows that imported successfully are kept. Only retry a batch that failed before importing any vehicles.')
                    ->visible(fn (ImportBatch $record) => $record->status === ImportStatus::FAILED && $record->success_rows === 0)
                    ->action(function (ImportBatch $record) {
                        $record->errors()->delete();

                        $record->forceFill([
                            'status' => ImportStatus::PENDING,
                            'processed_rows' => 0,
                            'success_rows' => 0,
                            'error_rows' => 0,
                            'error_report_path' => null,
                            'started_at' => null,
                            'finished_at' => null,
                        ])->save();

                        ProcessImportBatch::dispatch($record);
                    }),
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
            ImportErrorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportBatches::route('/'),
            'create' => Pages\CreateImportBatch::route('/create'),
            'view' => Pages\ViewImportBatch::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('status', [ImportStatus::PENDING->value, ImportStatus::PROCESSING->value])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function downloadTemplate(): StreamedResponse
    {
        return VehicleImportTemplate::download();
    }

    public static function downloadReport(ImportBatch $record): ?StreamedResponse
    {
        if (blank($record->error_report_path)) {
            return null;
        }

        $disk = Storage::disk($record->disk ?: 'local');

        if (! $disk->exists($record->error_report_path)) {
            return null;
        }

        return $disk->download(
            $record->error_report_path,
            'import-errors-'.$record->uuid.'.csv'
        );
    }
}
