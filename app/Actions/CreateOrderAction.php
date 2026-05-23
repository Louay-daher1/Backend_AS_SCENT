<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Discount;
use App\Models\Order;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        protected PricingService $pricingService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Order
    {
        $pricing = $this->pricingService->calculate(
            $payload['items'],
            $payload['discount_code'] ?? null,
        );

        return DB::transaction(function () use ($payload, $pricing) {
            $customer = $payload['customer'];

            $user = User::query()->updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    'name' => $customer['name'],
                    'role' => UserRole::Customer,
                    'password' => null,
                ],
            );

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'payment_method' => PaymentMethod::Cod,
                'subtotal' => $pricing['subtotal'],
                'quantity_savings' => $pricing['quantity_savings'],
                'discount_amount' => $pricing['discount_amount'],
                'total' => $pricing['total'],
                'discount_id' => $pricing['discount']?->id,
                'discount_code' => $pricing['discount']?->code,
                'address' => $customer['address'],
                'city' => $customer['city'],
                'notes' => $customer['notes'] ?? null,
            ]);

            foreach ($pricing['lines'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'size' => $line['size'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_subtotal' => $line['line_subtotal'],
                    'quantity_tier_savings' => $line['quantity_tier_savings'],
                    'discount_amount' => $line['discount_amount'],
                    'line_total' => $line['line_total'],
                ]);

                if ($line['variant']->stock !== null) {
                    $line['variant']->decrement('stock', $line['quantity']);
                }
            }

            foreach ($pricing['adjustments'] as $adjustment) {
                $order->adjustments()->create($adjustment);
            }

            $this->recordDiscountUsage($pricing);

            $order->load(['items', 'adjustments', 'discount', 'user']);

            Log::info('Order placed', [
                'order_number' => $order->order_number,
                'total' => $order->total,
            ]);

            return $order;
        });
    }

    protected function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    protected function assertDiscountsAvailable(array $pricing): void
    {
        foreach ($this->pricingService->appliedDiscounts($pricing) as $discount) {
            $locked = Discount::query()->whereKey($discount->id)->lockForUpdate()->first();

            if (! $locked?->isValidNow()) {
                throw ValidationException::withMessages([
                    'items' => ['A promotion in your cart is no longer available. Refresh the page and try again.'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    protected function recordDiscountUsage(array $pricing): void
    {
        foreach ($this->pricingService->appliedDiscounts($pricing) as $discount) {
            Discount::query()->whereKey($discount->id)->lockForUpdate()->increment('usage_count');
        }
    }
}
