<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardAccountWidget;
use App\Filament\Widgets\OrderStatsOverview;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            OrderStatsOverview::class,
            DashboardAccountWidget::class,
        ];
    }

    public function mount(): void
    {
        if (blank($this->filters)) {
            $this->filters = [
                'month' => now()->month,
                'year' => now()->year,
            ];
        }
    }

    public function filtersForm(Schema $schema): Schema
    {
        $currentYear = (int) now()->year;

        $years = collect(range($currentYear - 5, $currentYear))
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->all();

        $months = collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ])
            ->all();

        return $schema
            ->components([
                Section::make('Report period')
                    ->description('Sales and revenue use the month and year you select below.')
                    ->schema([
                        Select::make('month')
                            ->label('Month')
                            ->options($months)
                            ->default(now()->month)
                            ->required()
                            ->native(false),
                        Select::make('year')
                            ->label('Year')
                            ->options($years)
                            ->default($currentYear)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
