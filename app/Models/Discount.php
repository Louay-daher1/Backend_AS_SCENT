<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'discount_type',
        'discount_percent',
        'discount_value',
        'product_id',
        'is_active',
        'min_order_amount',
        'usage_limit',
        'usage_count',
        'created_by',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->start_time && $this->start_time->isFuture()) {
            return false;
        }

        if ($this->end_time && $this->end_time->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function badgeLabel(): string
    {
        return match ($this->discount_type) {
            DiscountType::Percent => ($this->discount_percent ?? 0).'% OFF',
            DiscountType::FixedAmount => '$'.number_format((float) ($this->discount_value ?? 0), 2).' OFF',
        };
    }

    public function saleUnitPrice(float $originalPrice): float
    {
        $sale = match ($this->discount_type) {
            DiscountType::Percent => $originalPrice * (1 - ((float) ($this->discount_percent ?? 0) / 100)),
            DiscountType::FixedAmount => $originalPrice - (float) ($this->discount_value ?? 0),
        };

        return Money::round(max(0, $sale));
    }

    public function reducesPrice(float $originalPrice): bool
    {
        return $this->saleUnitPrice($originalPrice) < Money::round($originalPrice);
    }

    /**
     * @return array<string, mixed>
     */
    public function toCatalogArray(): array
    {
        return [
            'label' => $this->badgeLabel(),
            'name' => $this->name,
            'type' => $this->discount_type->value,
            'percent' => $this->discount_percent,
            'amount' => $this->discount_value !== null ? (float) $this->discount_value : null,
        ];
    }
}
