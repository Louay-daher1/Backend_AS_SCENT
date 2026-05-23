<?php

namespace App\Support;

use BackedEnum;

class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<string|int, string>
     */
    public static function for(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->mapWithKeys(fn (BackedEnum $case): array => [$case->value => $case->name])
            ->all();
    }
}
