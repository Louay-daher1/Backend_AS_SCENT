<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Collection;

class CatalogDiscountService
{
    /** @var Collection<int, Discount>|null */
    protected ?Collection $cachedDiscounts = null;

    /**
     * @return Collection<int, Discount>
     */
    public function activeDiscounts(): Collection
    {
        if ($this->cachedDiscounts !== null) {
            return $this->cachedDiscounts;
        }

        $this->cachedDiscounts = Discount::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('start_time')->orWhere('start_time', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('end_time')->orWhere('end_time', '>=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->get()
            ->filter(fn (Discount $discount) => $discount->isValidNow())
            ->values();

        return $this->cachedDiscounts;
    }

    public function discountForProduct(int $productId): ?Discount
    {
        $discounts = $this->activeDiscounts();
        $specific = $discounts->where('product_id', $productId);

        return $this->pickBest($specific) ?? $this->pickBest($discounts->whereNull('product_id'));
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public function attachToProducts(Collection $products): void
    {
        foreach ($products as $product) {
            $product->setAttribute('catalog_discount', $this->discountForProduct($product->id));
        }
    }

    public function attachToProduct(Product $product): void
    {
        $product->setAttribute('catalog_discount', $this->discountForProduct($product->id));
    }

    /**
     * @return array{size: string, price: float, stock: int|null, sale_price?: float}
     */
    public function variantPricePayload(string $size, float $originalPrice, ?int $stock, ?Discount $discount): array
    {
        $payload = [
            'size' => $size,
            'price' => $originalPrice,
            'stock' => $stock,
        ];

        if ($discount && $discount->reducesPrice($originalPrice)) {
            $payload['sale_price'] = $discount->saleUnitPrice($originalPrice);
        }

        return $payload;
    }

    /**
     * @param  Collection<int, Discount>  $discounts
     */
    protected function pickBest(Collection $discounts): ?Discount
    {
        if ($discounts->isEmpty()) {
            return null;
        }

        return $discounts
            ->sortByDesc(fn (Discount $discount) => $this->badgeScore($discount))
            ->first();
    }

    protected function badgeScore(Discount $discount): float
    {
        return match ($discount->discount_type) {
            DiscountType::Percent => (float) ($discount->discount_percent ?? 0),
            DiscountType::FixedAmount => (float) ($discount->discount_value ?? 0),
        };
    }
}
