<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductQuantityTier;
use App\Models\ProductVariant;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => env('FILAMENT_ADMIN_EMAIL', 'admin@scentsbyas.com')],
            [
                'name' => env('FILAMENT_ADMIN_NAME', 'Admin User'),
                'password' => env('FILAMENT_ADMIN_PASSWORD', 'password'),
                'role' => UserRole::Admin,
            ],
        );

        $categories = collect([
            ['name' => 'Oriental', 'slug' => 'oriental', 'sort_order' => 1],
            ['name' => 'Floral', 'slug' => 'floral', 'sort_order' => 2],
            ['name' => 'Woody', 'slug' => 'woody', 'sort_order' => 3],
            ['name' => 'Fresh', 'slug' => 'fresh', 'sort_order' => 4],
        ])->mapWithKeys(function (array $data) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );

            return [$data['name'] => $category];
        });

        $products = [
            [
                'slug' => 'golden-oud',
                'name' => 'Golden Oud',
                'tagline' => 'A journey through ancient amber trails',
                'description' => 'An opulent blend of rare oud wood and warm amber, enriched with saffron threads and velvety sandalwood.',
                'category' => 'Oriental',
                'image' => 'perfume-1.jpg',
                'prices' => [['size' => '50ml', 'price' => 85], ['size' => '100ml', 'price' => 140]],
                'sort_order' => 1,
            ],
            [
                'slug' => 'rose-velvet',
                'name' => 'Rose Velvet',
                'tagline' => 'Soft petals on silk',
                'description' => 'A romantic symphony of Bulgarian rose and peony, wrapped in a soft musky veil.',
                'category' => 'Floral',
                'image' => 'perfume-2.jpg',
                'prices' => [['size' => '50ml', 'price' => 75], ['size' => '100ml', 'price' => 120]],
                'sort_order' => 2,
            ],
            [
                'slug' => 'midnight-sapphire',
                'name' => 'Midnight Sapphire',
                'tagline' => 'The mystery of midnight skies',
                'description' => 'A bold, magnetic fragrance that captures the essence of a starlit night.',
                'category' => 'Woody',
                'image' => 'perfume-3.jpg',
                'prices' => [['size' => '50ml', 'price' => 95], ['size' => '100ml', 'price' => 155]],
                'sort_order' => 3,
            ],
            [
                'slug' => 'royal-amber',
                'name' => 'Royal Amber',
                'tagline' => 'Crown jewel of fragrances',
                'description' => 'A regal composition of precious amber resin, enriched with rare spices and golden honey.',
                'category' => 'Oriental',
                'image' => 'perfume-4.jpg',
                'prices' => [['size' => '50ml', 'price' => 110], ['size' => '100ml', 'price' => 180]],
                'sort_order' => 4,
            ],
            [
                'slug' => 'fresh-citron',
                'name' => 'Fresh Citron',
                'tagline' => 'Morning dew on Mediterranean groves',
                'description' => 'A vibrant burst of Italian citrus and green tea, balanced with white cedar and light musk.',
                'category' => 'Fresh',
                'image' => 'perfume-5.jpg',
                'prices' => [['size' => '50ml', 'price' => 65], ['size' => '100ml', 'price' => 105]],
                'sort_order' => 5,
            ],
            [
                'slug' => 'pearl-mist',
                'name' => 'Pearl Mist',
                'tagline' => 'Whispers of ocean breeze',
                'description' => 'A delicate, luminous fragrance evoking the iridescence of pearls.',
                'category' => 'Fresh',
                'image' => 'perfume-6.jpg',
                'prices' => [['size' => '50ml', 'price' => 70], ['size' => '100ml', 'price' => 115]],
                'sort_order' => 6,
            ],
        ];

        Storage::disk('public')->makeDirectory('products');

        $frontendAssets = dirname(base_path(), 2).DIRECTORY_SEPARATOR.'perfume-palace-checkout'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'assets';

        foreach ($products as $index => $data) {
            $category = $categories[$data['category']];
            $imageFilename = $data['image'];
            $storagePath = 'products/'.$data['slug'].'.'.pathinfo($imageFilename, PATHINFO_EXTENSION);

            $sourcePath = collect([
                $frontendAssets.DIRECTORY_SEPARATOR.$imageFilename,
                base_path('..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'perfume-palace-checkout'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$imageFilename),
            ])->first(fn (string $path) => File::exists($path));

            if ($sourcePath) {
                File::ensureDirectoryExists(Storage::disk('public')->path('products'));
                File::copy($sourcePath, Storage::disk('public')->path($storagePath));
            }

            $product = Product::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'tagline' => $data['tagline'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'sort_order' => $data['sort_order'],
                ],
            );

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                [
                    'image_url' => $storagePath,
                    'sort_order' => 0,
                ],
            );

            foreach ($data['prices'] as $price) {
                ProductVariant::query()->updateOrCreate(
                    ['product_id' => $product->id, 'size' => $price['size']],
                    ['price' => $price['price']],
                );
            }

            if ($data['slug'] === 'golden-oud') {
                ProductQuantityTier::query()->updateOrCreate(
                    ['product_id' => $product->id, 'product_variant_id' => null, 'min_quantity' => 1],
                    ['savings' => 0, 'label' => 'Standard', 'is_active' => true],
                );
                ProductQuantityTier::query()->updateOrCreate(
                    ['product_id' => $product->id, 'product_variant_id' => null, 'min_quantity' => 3],
                    ['savings' => 10, 'label' => 'Buy 3, save $10', 'is_active' => true],
                );
            }

            if ($index === 0) {
                Slide::query()->updateOrCreate(
                    ['title' => 'Discover Golden Oud'],
                    [
                        'description' => 'Experience the grandeur of Arabian palaces.',
                        'image_url' => $storagePath,
                        'route' => '/product/golden-oud',
                        'button_text' => 'Shop Now',
                        'is_active' => true,
                        'show_logo' => true,
                        'product_id' => $product->id,
                        'category_id' => null,
                        'sort_order' => 1,
                    ],
                );
            }
        }

        $oriental = $categories['Oriental'];
        Slide::query()->updateOrCreate(
            ['title' => 'Explore Oriental Collection'],
            [
                'description' => 'Warm amber trails and rare oud.',
                'image_url' => 'products/golden-oud.jpg',
                'route' => '/products?category=Oriental',
                'button_text' => 'Explore Collection',
                'is_active' => true,
                'show_logo' => false,
                'product_id' => null,
                'category_id' => $oriental->id,
                'sort_order' => 2,
            ],
        );

        Discount::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Welcome 10% Off',
                'description' => '10% off your first order',
                'discount_type' => DiscountType::Percent,
                'discount_percent' => 10,
                'discount_value' => null,
                'product_id' => null,
                'is_active' => true,
                'min_order_amount' => 50,
                'usage_limit' => 100,
                'usage_count' => 0,
                'created_by' => $admin->id,
                'start_time' => now()->subDay(),
                'end_time' => now()->addYear(),
            ],
        );
    }
}
