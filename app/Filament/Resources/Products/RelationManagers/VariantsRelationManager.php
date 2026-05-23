<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('size')
                    ->suffix('ml')
                    ->helperText('Enter the volume number only, e.g. 50 or 100.')
                    ->required()
                    ->maxLength(50),
                TextInput::make('price')
                    ->label('Price ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0)
                    ->required(),
                TextInput::make('stock')
                    ->numeric()
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('size')
                    ->formatStateUsing(fn (string $state): string => preg_replace('/\s*ml\s*$/i', '', trim($state)).' ml'),
                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => Money::formatUsd($state)),
                TextColumn::make('stock'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
