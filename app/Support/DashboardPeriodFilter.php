<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class DashboardPeriodFilter
{
    /**
     * @param  array<string, mixed>|null  $filters
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}
     */
    public static function resolve(?array $filters): array
    {
        $month = isset($filters['month']) ? (int) $filters['month'] : (int) now()->month;
        $year = isset($filters['year']) ? (int) $filters['year'] : (int) now()->year;

        $month = max(1, min(12, $month));
        $year = max(2000, min((int) now()->year + 1, $year));

        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('F Y'),
        ];
    }
}
