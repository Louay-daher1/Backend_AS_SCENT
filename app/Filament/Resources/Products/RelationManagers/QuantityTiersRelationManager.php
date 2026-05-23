<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuantityTiersRelationManager extends RelationManager
{
    protected static string $relationship = 'quantityTiers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('min_quantity')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('savings')
                    ->label('Savings ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0)
                    ->required(),
                TextInput::make('label')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('min_quantity'),
                TextColumn::make('savings')
                    ->label('Savings')
                    ->formatStateUsing(fn ($state) => Money::formatUsd($state)),
                TextColumn::make('label'),
                IconColumn::make('is_active')->boolean(),
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
