<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percent = 'percent';
    case FixedAmount = 'fixed_amount';
}
