<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BestSellerService
{
    /**
     * Active products ranked by total units sold (order_items.quantity), excluding cancelled orders.
     *
     * @return Collection<int, Product>
     */
    public function topProducts(int $limit = 10): Collection
    {
        $limit = min(max($limit, 1), 50);

        $rankedIds = $this->rankedProductIds();

        if ($rankedIds->isNotEmpty()) {
            return $this->productsForIds($rankedIds->take($limit));
        }

        return Product::query()
            ->with(['category', 'variants', 'primaryImage'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function paginateTopProducts(int $page, int $perPage, ?int $categoryId = null): LengthAwarePaginator
    {
        $rankedIds = $this->rankedProductIds($categoryId);

        if ($rankedIds->isEmpty()) {
            return Product::query()
                ->with(['category', 'variants', 'primaryImage'])
                ->where('is_active', true)
                ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
                ->orderBy('sort_order')
                ->paginate($perPage, ['*'], 'page', $page);
        }

        $total = $rankedIds->count();
        $pageIds = $rankedIds->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $this->productsForIds($pageIds);

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @return Collection<int, int>
     */
    protected function rankedProductIds(?int $categoryId = null): Collection
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->where('products.is_active', true);

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        return $query
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->pluck('order_items.product_id');
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, Product>
     */
    protected function productsForIds(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $rank = $ids->values()->flip();

        return Product::query()
            ->with(['category', 'variants', 'primaryImage'])
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Product $product) => $rank->get($product->id, PHP_INT_MAX))
            ->values();
    }
}
