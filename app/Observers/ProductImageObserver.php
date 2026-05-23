<?php

namespace App\Observers;

use App\Models\ProductImage;

class ProductImageObserver
{
    public function saving(ProductImage $image): void
    {
        if (! $image->is_primary) {
            return;
        }

        ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('id', '!=', $image->id ?? 0)
            ->update(['is_primary' => false]);
    }
}
