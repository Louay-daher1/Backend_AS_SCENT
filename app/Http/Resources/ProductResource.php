<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Services\CatalogDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $primaryImage = $this->relationLoaded('primaryImage')
            ? $this->primaryImage
            : $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        $catalogDiscount = $this->resource->getAttribute('catalog_discount');
        if (! $catalogDiscount instanceof Discount) {
            $catalogDiscount = app(CatalogDiscountService::class)->discountForProduct($this->id);
        }

        $catalog = app(CatalogDiscountService::class);

        $data = [
            'product_id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'image' => $this->publicImageUrl($primaryImage),
            'category' => $this->category?->name,
            'prices' => $this->variants->map(fn ($variant) => $catalog->variantPricePayload(
                $variant->size,
                (float) $variant->price,
                $variant->stock,
                $catalogDiscount instanceof Discount ? $catalogDiscount : null,
            ))->values(),
        ];

        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $data['images'] = $this->images->map(fn ($image) => [
                'url' => $this->publicImageUrl($image),
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ])->values();
        }

        if ($this->relationLoaded('quantityTiers')) {
            $data['quantity_offers'] = $this->quantityTiers
                ->filter(fn ($tier) => $tier->is_active)
                ->map(fn ($tier) => [
                    'min_quantity' => $tier->min_quantity,
                    'savings' => (float) $tier->savings,
                    'label' => $tier->label,
                ])
                ->values();
        }

        if ($catalogDiscount instanceof Discount) {
            $data['discount'] = $catalogDiscount->toCatalogArray();
        }

        return $data;
    }

    private function publicImageUrl(?\App\Models\ProductImage $image): ?string
    {
        $path = $image?->storagePath();

        if (! $path) {
            return null;
        }

        return '/storage/'.$path;
    }
}
