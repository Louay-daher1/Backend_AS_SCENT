<?php

namespace App\Support;

class Money
{
    public static function round(float|int|string $amount): float
    {
        return round((float) $amount, 2);
    }

    public static function format(float|int|string|null $amount): string
    {
        if ($amount === null) {
            return '0.00';
        }

        return number_format((float) $amount, 2, '.', '');
    }

    public static function formatUsd(float|int|string|null $amount): string
    {
        return '$'.self::format($amount);
    }
}
