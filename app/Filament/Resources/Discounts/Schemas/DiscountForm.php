<?php

namespace App\Filament\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use App\Support\EnumOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                Textarea::make('description')->columnSpanFull(),
                Select::make('discount_type')
                    ->options(EnumOptions::for(DiscountType::class))
                    ->required(),
                TextInput::make('discount_percent')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('discount_value')
                    ->label('Fixed amount ($)')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->nullable(),
                TextInput::make('usage_limit')
                    ->numeric()
                    ->nullable(),
                DateTimePicker::make('start_time'),
                DateTimePicker::make('end_time'),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
