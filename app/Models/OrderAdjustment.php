<?php

namespace App\Models;

use App\Enums\OrderAdjustmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAdjustment extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'reference_id',
        'label',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderAdjustmentType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
