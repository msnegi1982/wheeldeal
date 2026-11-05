<?php

namespace App\Filament\Resources\CustomerRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notes';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('note')
                ->required()
                ->rows(4)
                ->maxLength(2000)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_internal')
                ->label('Internal only')
                ->default(true)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('note')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_internal')
                    ->boolean()
                    ->label('Internal'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data) => array_merge($data, [
                        'user_id' => auth()->id(),
                    ])),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
