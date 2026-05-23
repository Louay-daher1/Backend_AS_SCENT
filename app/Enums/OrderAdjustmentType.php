<?php

namespace App\Enums;

enum OrderAdjustmentType: string
{
    case QuantityTier = 'quantity_tier';
    case Coupon = 'coupon';
    case Manual = 'manual';
}
