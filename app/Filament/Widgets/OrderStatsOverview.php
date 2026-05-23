<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\DashboardPeriodFilter;
use App\Support\Money;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Store overview';

    protected function getStats(): array
    {
        $period = DashboardPeriodFilter::resolve($this->pageFilters);

        $pendingCount = Order::query()
            ->where('status', OrderStatus::Pending)
            ->count();

        $completedCount = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->count();

        $periodOrdersQuery = Order::query()
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->whereNot('status', OrderStatus::Cancelled);

        $salesInPeriod = (clone $periodOrdersQuery)->count();
        $revenue = (float) (clone $periodOrdersQuery)->sum('total');

        return [
            Stat::make('Pending orders', (string) $pendingCount)
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => OrderStatus::Pending->value],
                    ],
                ])),
            Stat::make('Completed orders', (string) $completedCount)
                ->description('Delivered (all time)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => [
                        'status' => ['value' => OrderStatus::Delivered->value],
                    ],
                ])),
            Stat::make('Sales', (string) $salesInPeriod)
                ->description("Orders in {$period['label']}")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->url(OrderResource::getUrl('index')),
            Stat::make('Revenue', Money::formatUsd($revenue))
                ->description("Excludes cancelled · {$period['label']}")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->url(OrderResource::getUrl('index')),
        ];
    }
}
