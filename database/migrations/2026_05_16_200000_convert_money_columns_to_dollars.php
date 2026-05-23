<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertVariantPrices();
        $this->convertQuantityTierSavings();
        $this->convertDiscountAmounts();
        $this->convertOrderTotals();
        $this->convertOrderItems();
        $this->convertOrderAdjustments();
    }

    public function down(): void
    {
        $this->revertOrderAdjustments();
        $this->revertOrderItems();
        $this->revertOrderTotals();
        $this->revertDiscountAmounts();
        $this->revertQuantityTierSavings();
        $this->revertVariantPrices();
    }

    protected function centsToDollars(int $cents): float
    {
        return $cents >= 1000 ? round($cents / 100, 2) : round($cents, 2);
    }

    protected function dollarsToCents(float $dollars): int
    {
        return (int) round($dollars * 100);
    }

    protected function convertVariantPrices(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('size');
        });

        foreach (DB::table('product_variants')->get() as $row) {
            DB::table('product_variants')->where('id', $row->id)->update([
                'price' => $this->centsToDollars((int) $row->price_cents),
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('price_cents');
        });
    }

    protected function convertQuantityTierSavings(): void
    {
        Schema::table('product_quantity_tiers', function (Blueprint $table) {
            $table->decimal('savings', 10, 2)->default(0)->after('min_quantity');
        });

        foreach (DB::table('product_quantity_tiers')->get() as $row) {
            DB::table('product_quantity_tiers')->where('id', $row->id)->update([
                'savings' => $this->centsToDollars((int) $row->savings_cents),
            ]);
        }

        Schema::table('product_quantity_tiers', function (Blueprint $table) {
            $table->dropColumn('savings_cents');
        });
    }

    protected function convertDiscountAmounts(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_percent');
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('is_active');
        });

        foreach (DB::table('discounts')->get() as $row) {
            DB::table('discounts')->where('id', $row->id)->update([
                'discount_value' => $row->discount_value_cents !== null
                    ? $this->centsToDollars((int) $row->discount_value_cents)
                    : null,
                'min_order_amount' => $row->min_order_amount_cents !== null
                    ? $this->centsToDollars((int) $row->min_order_amount_cents)
                    : null,
            ]);
        }

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['discount_value_cents', 'min_order_amount_cents']);
        });
    }

    protected function convertOrderTotals(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('payment_method');
            $table->decimal('quantity_savings', 10, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('quantity_savings');
            $table->decimal('total', 10, 2)->default(0)->after('discount_amount');
        });

        foreach (DB::table('orders')->get() as $row) {
            DB::table('orders')->where('id', $row->id)->update([
                'subtotal' => $this->centsToDollars((int) $row->subtotal_cents),
                'quantity_savings' => $this->centsToDollars((int) $row->quantity_savings_cents),
                'discount_amount' => $this->centsToDollars((int) $row->discount_cents),
                'total' => $this->centsToDollars((int) $row->total_cents),
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_cents', 'quantity_savings_cents', 'discount_cents', 'total_cents']);
        });
    }

    protected function convertOrderItems(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            $table->decimal('line_subtotal', 10, 2)->default(0)->after('unit_price');
            $table->decimal('quantity_tier_savings', 10, 2)->default(0)->after('line_subtotal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('quantity_tier_savings');
            $table->decimal('line_total', 10, 2)->default(0)->after('discount_amount');
        });

        foreach (DB::table('order_items')->get() as $row) {
            DB::table('order_items')->where('id', $row->id)->update([
                'unit_price' => $this->centsToDollars((int) $row->unit_price_cents),
                'line_subtotal' => $this->centsToDollars((int) $row->line_subtotal_cents),
                'quantity_tier_savings' => $this->centsToDollars((int) $row->quantity_tier_savings_cents),
                'discount_amount' => $this->centsToDollars((int) $row->discount_cents),
                'line_total' => $this->centsToDollars((int) $row->line_total_cents),
            ]);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_price_cents',
                'line_subtotal_cents',
                'quantity_tier_savings_cents',
                'discount_cents',
                'line_total_cents',
            ]);
        });
    }

    protected function convertOrderAdjustments(): void
    {
        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->default(0)->after('label');
        });

        foreach (DB::table('order_adjustments')->get() as $row) {
            DB::table('order_adjustments')->where('id', $row->id)->update([
                'amount' => $this->centsToDollars(abs((int) $row->amount_cents)) * ($row->amount_cents < 0 ? -1 : 1),
            ]);
        }

        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->dropColumn('amount_cents');
        });
    }

    protected function revertVariantPrices(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('price_cents')->default(0)->after('size');
        });

        foreach (DB::table('product_variants')->get() as $row) {
            DB::table('product_variants')->where('id', $row->id)->update([
                'price_cents' => $this->dollarsToCents((float) $row->price),
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    protected function revertQuantityTierSavings(): void
    {
        Schema::table('product_quantity_tiers', function (Blueprint $table) {
            $table->unsignedInteger('savings_cents')->default(0)->after('min_quantity');
        });

        foreach (DB::table('product_quantity_tiers')->get() as $row) {
            DB::table('product_quantity_tiers')->where('id', $row->id)->update([
                'savings_cents' => $this->dollarsToCents((float) $row->savings),
            ]);
        }

        Schema::table('product_quantity_tiers', function (Blueprint $table) {
            $table->dropColumn('savings');
        });
    }

    protected function revertDiscountAmounts(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedInteger('discount_value_cents')->nullable()->after('discount_percent');
            $table->unsignedInteger('min_order_amount_cents')->nullable()->after('is_active');
        });

        foreach (DB::table('discounts')->get() as $row) {
            DB::table('discounts')->where('id', $row->id)->update([
                'discount_value_cents' => $row->discount_value !== null
                    ? $this->dollarsToCents((float) $row->discount_value)
                    : null,
                'min_order_amount_cents' => $row->min_order_amount !== null
                    ? $this->dollarsToCents((float) $row->min_order_amount)
                    : null,
            ]);
        }

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['discount_value', 'min_order_amount']);
        });
    }

    protected function revertOrderTotals(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('quantity_savings_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
        });

        foreach (DB::table('orders')->get() as $row) {
            DB::table('orders')->where('id', $row->id)->update([
                'subtotal_cents' => $this->dollarsToCents((float) $row->subtotal),
                'quantity_savings_cents' => $this->dollarsToCents((float) $row->quantity_savings),
                'discount_cents' => $this->dollarsToCents((float) $row->discount_amount),
                'total_cents' => $this->dollarsToCents((float) $row->total),
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'quantity_savings', 'discount_amount', 'total']);
        });
    }

    protected function revertOrderItems(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('line_subtotal_cents')->default(0);
            $table->unsignedInteger('quantity_tier_savings_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('line_total_cents')->default(0);
        });

        foreach (DB::table('order_items')->get() as $row) {
            DB::table('order_items')->where('id', $row->id)->update([
                'unit_price_cents' => $this->dollarsToCents((float) $row->unit_price),
                'line_subtotal_cents' => $this->dollarsToCents((float) $row->line_subtotal),
                'quantity_tier_savings_cents' => $this->dollarsToCents((float) $row->quantity_tier_savings),
                'discount_cents' => $this->dollarsToCents((float) $row->discount_amount),
                'line_total_cents' => $this->dollarsToCents((float) $row->line_total),
            ]);
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_price',
                'line_subtotal',
                'quantity_tier_savings',
                'discount_amount',
                'line_total',
            ]);
        });
    }

    protected function revertOrderAdjustments(): void
    {
        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->integer('amount_cents')->default(0);
        });

        foreach (DB::table('order_adjustments')->get() as $row) {
            $amount = (float) $row->amount;
            DB::table('order_adjustments')->where('id', $row->id)->update([
                'amount_cents' => $amount < 0
                    ? -$this->dollarsToCents(abs($amount))
                    : $this->dollarsToCents($amount),
            ]);
        }

        Schema::table('order_adjustments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
