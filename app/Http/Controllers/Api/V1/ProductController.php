<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\BestSellerService;
use App\Services\CatalogDiscountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const CATALOG_SORTS = ['latest', 'bestseller', 'price_asc', 'price_desc'];

    public function __construct(
        protected CatalogDiscountService $catalogDiscounts,
        protected BestSellerService $bestSellers,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = $this->resolveCatalogSort($request);

        if ($sort === 'bestseller' && $request->filled('limit') && ! $request->has('page')) {
            $limit = min(max((int) $request->query('limit'), 1), 50);
            $products = $this->bestSellers->topProducts($limit);
            $this->catalogDiscounts->attachToProducts($products);

            return ProductResource::collection($products);
        }

        $query = $this->baseProductQuery();

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('name', $category));
        }

        if ($request->filled('limit') && ! $request->has('page')) {
            $this->applyCatalogSort($query, $sort);
            $limit = min(max((int) $request->query('limit'), 1), 50);
            $products = $query->limit($limit)->get();
            $this->catalogDiscounts->attachToProducts($products);

            return ProductResource::collection($products);
        }

        $perPage = min(max((int) $request->query('per_page', 12), 1), 48);
        $page = max((int) $request->query('page', 1), 1);
        $categoryId = $this->resolveCategoryId($request->query('category'));

        if ($sort === 'bestseller') {
            $paginator = $this->bestSellers->paginateTopProducts($page, $perPage, $categoryId);
        } else {
            $this->applyCatalogSort($query, $sort);
            $paginator = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
        }

        $this->catalogDiscounts->attachToProducts($paginator->getCollection());

        return ProductResource::collection($paginator);
    }

    public function show(int $productId): ProductResource
    {
        $product = Product::query()
            ->with([
                'category',
                'variants',
                'images',
                'quantityTiers' => fn ($query) => $query->where('is_active', true),
                'primaryImage',
            ])
            ->whereKey($productId)
            ->where('is_active', true)
            ->firstOrFail();

        $this->catalogDiscounts->attachToProduct($product);

        return new ProductResource($product);
    }

    public function variantPrice(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->firstOrFail();

        $variant = $product->variants()
            ->where('size', $validated['size'])
            ->firstOrFail();

        $discount = $this->catalogDiscounts->discountForProduct($product->id);

        return response()->json([
            ...$this->catalogDiscounts->variantPricePayload(
                $variant->size,
                (float) $variant->price,
                $variant->stock,
                $discount,
            ),
            'product_id' => $product->id,
        ]);
    }

    protected function baseProductQuery(): Builder
    {
        return Product::query()
            ->with(['category', 'variants', 'primaryImage'])
            ->where('is_active', true);
    }

    protected function resolveCatalogSort(Request $request): ?string
    {
        $sort = $request->query('sort');

        if ($sort === null || $sort === '') {
            return null;
        }

        $request->validate([
            'sort' => ['string', Rule::in(self::CATALOG_SORTS)],
        ]);

        return $sort;
    }

    protected function applyCatalogSort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'latest' => $query->orderByDesc('created_at'),
            'price_asc' => $query->orderByRaw(
                '(SELECT MIN(price) FROM product_variants WHERE product_variants.product_id = products.id) ASC',
            ),
            'price_desc' => $query->orderByRaw(
                '(SELECT MIN(price) FROM product_variants WHERE product_variants.product_id = products.id) DESC',
            ),
            default => $query->orderBy('sort_order'),
        };
    }

    protected function resolveCategoryId(?string $categoryName): ?int
    {
        if (! $categoryName) {
            return null;
        }

        $id = Category::query()->where('name', $categoryName)->value('id');

        return $id ? (int) $id : null;
    }
}
