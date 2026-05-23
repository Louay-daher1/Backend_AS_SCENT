<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuantityTier extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'min_quantity',
        'savings',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'savings' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
