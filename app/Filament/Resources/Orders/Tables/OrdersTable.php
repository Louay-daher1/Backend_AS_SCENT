<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\EnumOptions;
use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable(['users.name'])
                    ->label('Customer')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, Order $record): string => $state ?? $record->user?->name ?? '—'),
                TextColumn::make('user.phone')
                    ->searchable(['users.phone'])
                    ->label('Phone')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, Order $record): string => $state ?? $record->user?->phone ?? '—'),
                TextColumn::make('city'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => Money::formatUsd($state))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumOptions::for(OrderStatus::class)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Details'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
