<?php

namespace App\Filament\Resources\Discounts\Tables;

use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('discount_percent')
                    ->label('%')
                    ->suffix('%'),
                TextColumn::make('discount_value')
                    ->label('Fixed')
                    ->formatStateUsing(fn ($state) => $state !== null ? Money::formatUsd($state) : '—'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('All products'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('usage_count')
                    ->label('Used'),
                TextColumn::make('usage_limit')
                    ->label('Limit'),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
