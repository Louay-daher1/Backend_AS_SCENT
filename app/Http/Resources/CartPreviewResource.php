<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartPreviewResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'subtotal' => (float) $this->resource['subtotal'],
            'quantity_savings' => (float) $this->resource['quantity_savings'],
            'discount_savings' => (float) $this->resource['discount_amount'],
            'total' => (float) $this->resource['total'],
            'discount_code' => $this->resource['discount']?->code,
            'items' => collect($this->resource['lines'])->map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'product_name' => $line['product_name'],
                'size' => $line['size'],
                'quantity' => $line['quantity'],
                'unit_price' => (float) $line['unit_price'],
                'line_subtotal' => (float) $line['line_subtotal'],
                'quantity_tier_savings' => (float) $line['quantity_tier_savings'],
                'line_total' => (float) $line['line_total'],
            ])->values(),
            'adjustments' => collect($this->resource['adjustments'])->map(fn (array $adj) => [
                'type' => $adj['type']->value,
                'label' => $adj['label'],
                'amount' => (float) $adj['amount'],
            ])->values(),
        ];
    }
}
