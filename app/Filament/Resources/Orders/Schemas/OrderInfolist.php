<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\HtmlString;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Name')
                            ->state(fn (Order $record): string => $record->user?->name ?? '—')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large),
                        TextEntry::make('customer_phone')
                            ->label('Phone')
                            ->state(fn (Order $record): string => $record->user?->phone ?? '—')
                            ->icon('heroicon-o-phone')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Delivery')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                        TextEntry::make('city')
                            ->label('City'),
                        TextEntry::make('notes')
                            ->label('Customer notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Order summary')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Order #')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Placed at')
                            ->dateTime(),
                        TextEntry::make('payment_method')
                            ->label('Payment')
                            ->badge()
                            ->formatStateUsing(
                                fn (PaymentMethod|string|null $state): string => strtoupper(
                                    $state instanceof PaymentMethod ? $state->value : (string) ($state ?? ''),
                                ),
                            ),
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->formatStateUsing(fn ($state) => Money::formatUsd($state)),
                        TextEntry::make('quantity_savings')
                            ->label('Bundle savings')
                            ->formatStateUsing(fn ($state) => $state > 0 ? '-'.Money::formatUsd($state) : '—')
                            ->visible(fn (Order $record): bool => (float) $record->quantity_savings > 0),
                        TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->formatStateUsing(fn ($state) => $state > 0 ? '-'.Money::formatUsd($state) : '—')
                            ->visible(fn (Order $record): bool => (float) $record->discount_amount > 0),
                        TextEntry::make('discount_code')
                            ->label('Discount code')
                            ->placeholder('—')
                            ->visible(fn (Order $record): bool => filled($record->discount_code)),
                        TextEntry::make('total')
                            ->label('Total (COD)')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->formatStateUsing(fn ($state) => Money::formatUsd($state)),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Items')
                    ->schema([
                        TextEntry::make('items_table')
                            ->hiddenLabel()
                            ->state(fn (Order $record): HtmlString => new HtmlString(self::renderItemsTable($record)))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function renderItemsTable(Order $record): string
    {
        if ($record->items->isEmpty()) {
            return '<p style="margin:0;font-size:0.875rem;color:#6b7280;">No items.</p>';
        }

        $cell = 'padding:0.75rem 1rem;border:1px solid #d1d5db;vertical-align:top;font-size:0.875rem;';
        $head = $cell.'background-color:#f9fafb;font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#4b5563;';

        $rows = $record->items
            ->map(
                fn ($item) => sprintf(
                    '<tr>
                        <td style="%s">%s</td>
                        <td style="%s">%s</td>
                        <td style="%s text-align:right;">%d</td>
                        <td style="%s text-align:right;">%s</td>
                        <td style="%s text-align:right;font-weight:600;">%s</td>
                    </tr>',
                    $cell,
                    e($item->product_name),
                    $cell,
                    e($item->size),
                    $cell,
                    $item->quantity,
                    $cell,
                    e(Money::formatUsd($item->unit_price)),
                    $cell,
                    e(Money::formatUsd($item->line_total)),
                ),
            )
            ->implode('');

        return <<<HTML
            <div class="fi-ta-ctn overflow-x-auto" style="margin-top:0;">
                <table style="width:100%;min-width:32rem;border-collapse:collapse;border:1px solid #d1d5db;background-color:#fff;">
                    <thead>
                        <tr>
                            <th style="{$head} text-align:left;">Product</th>
                            <th style="{$head} text-align:left;">Size</th>
                            <th style="{$head} text-align:right;">Qty</th>
                            <th style="{$head} text-align:right;">Unit price</th>
                            <th style="{$head} text-align:right;">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
            HTML;
    }
}
