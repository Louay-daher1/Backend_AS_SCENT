<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\EnumOptions;
use App\Support\Money;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextInput::make('order_number')
                            ->disabled(),
                        Select::make('status')
                            ->options(EnumOptions::for(OrderStatus::class))
                            ->required(),
                        TextInput::make('payment_method')
                            ->disabled(),
                        TextInput::make('total')
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => $state !== null ? Money::formatUsd($state) : '')
                            ->disabled(),
                        TextInput::make('discount_code')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Customer')
                    ->schema([
                        Placeholder::make('customer_name')
                            ->label('Customer')
                            ->content(fn (?Order $record): string => $record?->user?->name ?? '—'),
                        Placeholder::make('customer_phone')
                            ->label('Phone')
                            ->content(fn (?Order $record): string => $record?->user?->phone ?? '—'),
                        TextInput::make('address')->disabled()->columnSpanFull(),
                        TextInput::make('city')->disabled(),
                        TextInput::make('notes')->disabled()->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        Placeholder::make('items_summary')
                            ->label('')
                            ->content(function ($record): string {
                                if (! $record) {
                                    return '—';
                                }

                                return $record->items
                                    ->map(fn ($item) => sprintf(
                                        '%s (%s) × %d — %s',
                                        $item->product_name,
                                        $item->size,
                                        $item->quantity,
                                        Money::formatUsd($item->line_total),
                                    ))
                                    ->implode("\n");
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
