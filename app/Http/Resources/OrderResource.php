<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method->value,
            'subtotal' => (float) $this->subtotal,
            'quantity_savings' => (float) $this->quantity_savings,
            'discount_savings' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'discount_code' => $this->discount_code,
            'customer' => [
                'name' => $this->user?->name,
                'phone' => $this->user?->phone,
            ],
            'items' => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'size' => $item->size,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ]),
            'adjustments' => $this->adjustments->map(fn ($adj) => [
                'type' => $adj->type->value,
                'label' => $adj->label,
                'amount' => (float) $adj->amount,
            ]),
        ];
    }
}
