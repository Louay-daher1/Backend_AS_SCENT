<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\OrderAdjustmentType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductQuantityTier;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(
        protected CatalogDiscountService $catalogDiscounts,
    ) {}

    /**
     * @param  array<int, array{product_id: int, size: string, quantity: int}>  $items
     * @return array<string, mixed>
     */
    public function calculate(array $items, ?string $discountCode = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => ['At least one item is required.'],
            ]);
        }

        $lines = [];
        $adjustments = [];
        $subtotalAfterTiers = 0.0;
        $totalQuantitySavings = 0.0;

        foreach ($items as $index => $item) {
            $line = $this->calculateLine($item, $index);
            $lines[] = $line;
            $subtotalAfterTiers += $line['line_total'];
            $totalQuantitySavings += $line['quantity_tier_savings'];

            if ($line['quantity_tier_savings'] > 0) {
                $adjustments[] = [
                    'type' => OrderAdjustmentType::QuantityTier,
                    'reference_id' => $line['tier_id'],
                    'label' => $line['tier_label'] ?? 'Quantity savings',
                    'amount' => -$line['quantity_tier_savings'],
                ];
            }
        }

        $subtotalBeforeTiers = Money::round(collect($lines)->sum('line_subtotal'));
        $discount = $this->resolveDiscount($discountCode, $subtotalAfterTiers, $lines);
        $discountAmount = 0.0;

        if ($discount) {
            $discountAmount = $this->applyDiscount($discount, $lines, $subtotalAfterTiers);
            $adjustments[] = [
                'type' => OrderAdjustmentType::Coupon,
                'reference_id' => $discount->id,
                'label' => $discount->name,
                'amount' => -$discountAmount,
            ];
        }

        $total = Money::round(max(0, $subtotalAfterTiers - $discountAmount));

        return [
            'lines' => $lines,
            'adjustments' => $adjustments,
            'discount' => $discount,
            'subtotal' => $subtotalBeforeTiers,
            'quantity_savings' => Money::round($totalQuantitySavings),
            'discount_amount' => Money::round($discountAmount),
            'total' => $total,
        ];
    }

    /**
     * @param  array{product_id: int, size: string, quantity: int}  $item
     * @return array<string, mixed>
     */
    protected function calculateLine(array $item, int $index): array
    {
        $quantity = (int) $item['quantity'];
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                "items.{$index}.quantity" => ['Quantity must be at least 1.'],
            ]);
        }

        $product = Product::query()
            ->with(['variants', 'quantityTiers'])
            ->whereKey((int) $item['product_id'])
            ->where('is_active', true)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                "items.{$index}.product_id" => ['Product not found or inactive.'],
            ]);
        }

        $variant = $product->variants->firstWhere('size', $item['size']);
        if (! $variant) {
            throw ValidationException::withMessages([
                "items.{$index}.size" => ['Invalid size for this product.'],
            ]);
        }

        if ($variant->stock !== null) {
            if ($variant->stock < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.size" => ['This size is out of stock.'],
                ]);
            }

            if ($variant->stock < $quantity) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => [
                        $variant->stock === 1
                            ? 'Only 1 item left in stock.'
                            : "Only {$variant->stock} available in stock.",
                    ],
                ]);
            }
        }

        $originalUnitPrice = (float) $variant->price;
        $catalogDiscount = $this->catalogDiscounts->discountForProduct($product->id);
        $appliedCatalogDiscount = $catalogDiscount && $catalogDiscount->reducesPrice($originalUnitPrice)
            ? $catalogDiscount
            : null;
        $unitPrice = $appliedCatalogDiscount
            ? $appliedCatalogDiscount->saleUnitPrice($originalUnitPrice)
            : $originalUnitPrice;
        $catalogSavings = Money::round(max(0, $originalUnitPrice - $unitPrice) * $quantity);

        $lineSubtotal = Money::round($unitPrice * $quantity);
        $tier = $this->resolveQuantityTier($product->quantityTiers, $variant->id, $quantity);
        $tierSavings = $tier ? min((float) $tier->savings, $lineSubtotal) : 0.0;
        $lineTotal = Money::round($lineSubtotal - $tierSavings);

        return [
            'product' => $product,
            'variant' => $variant,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'size' => $variant->size,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'original_unit_price' => $originalUnitPrice,
            'line_subtotal' => $lineSubtotal,
            'quantity_tier_savings' => Money::round($tierSavings),
            'discount_amount' => $catalogSavings,
            'line_total' => $lineTotal,
            'tier_id' => $tier?->id,
            'tier_label' => $tier?->label,
            'catalog_discount' => $appliedCatalogDiscount,
        ];
    }

    /**
     * Unique discounts applied to cart lines (catalog) plus an optional checkout code.
     *
     * @param  array<string, mixed>  $pricing
     * @return Collection<int, Discount>
     */
    public function appliedDiscounts(array $pricing): Collection
    {
        $fromLines = collect($pricing['lines'] ?? [])
            ->pluck('catalog_discount')
            ->filter();

        $coupon = $pricing['discount'] ?? null;

        if ($coupon) {
            $fromLines = $fromLines->push($coupon);
        }

        return $fromLines->unique('id')->values();
    }

    /**
     * @param  Collection<int, ProductQuantityTier>  $tiers
     */
    protected function resolveQuantityTier(Collection $tiers, int $variantId, int $quantity): ?ProductQuantityTier
    {
        $applicable = $tiers
            ->filter(function (ProductQuantityTier $tier) use ($variantId, $quantity) {
                if ($tier->product_variant_id !== null && $tier->product_variant_id !== $variantId) {
                    return false;
                }

                return $tier->min_quantity <= $quantity;
            })
            ->sortByDesc('min_quantity');

        return $applicable->first();
    }

    protected function resolveDiscount(?string $code, float $subtotalAfterTiers, array $lines): ?Discount
    {
        if (! $code) {
            return null;
        }

        $discount = Discount::query()
            ->where('code', $code)
            ->first();

        if (! $discount || ! $discount->isValidNow()) {
            throw ValidationException::withMessages([
                'discount_code' => ['Invalid or expired discount code.'],
            ]);
        }

        if ($discount->min_order_amount !== null && $subtotalAfterTiers < (float) $discount->min_order_amount) {
            throw ValidationException::withMessages([
                'discount_code' => ['Order does not meet minimum amount for this discount.'],
            ]);
        }

        return $discount;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function applyDiscount(Discount $discount, array &$lines, float $subtotalAfterTiers): float
    {
        $eligibleSubtotal = $subtotalAfterTiers;

        if ($discount->product_id) {
            $eligibleSubtotal = collect($lines)
                ->filter(fn (array $line) => $line['product_id'] === $discount->product_id)
                ->sum('line_total');
        }

        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        $discountAmount = match ($discount->discount_type) {
            DiscountType::Percent => Money::round($eligibleSubtotal * ($discount->discount_percent / 100)),
            DiscountType::FixedAmount => min((float) ($discount->discount_value ?? 0), $eligibleSubtotal),
        };

        if ($discount->product_id) {
            $remaining = $discountAmount;
            foreach ($lines as &$line) {
                if ($line['product_id'] !== $discount->product_id || $remaining <= 0) {
                    continue;
                }
                $share = min($line['line_total'], $remaining);
                $line['discount_amount'] = Money::round($share);
                $line['line_total'] = Money::round($line['line_total'] - $share);
                $remaining = Money::round($remaining - $share);
            }
        } else {
            $remaining = $discountAmount;
            foreach ($lines as &$line) {
                if ($remaining <= 0) {
                    break;
                }
                $share = Money::round($discountAmount * ($line['line_total'] / $subtotalAfterTiers));
                $share = min($share, $line['line_total'], $remaining);
                $line['discount_amount'] = $share;
                $line['line_total'] = Money::round($line['line_total'] - $share);
                $remaining = Money::round($remaining - $share);
            }
        }

        return $discountAmount;
    }
}
