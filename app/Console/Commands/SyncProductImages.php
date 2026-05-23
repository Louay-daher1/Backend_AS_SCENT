<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SyncProductImages extends Command
{
    protected $signature = 'products:sync-images';

    protected $description = 'Copy missing product images from the frontend assets folder into public storage';

    public function handle(): int
    {
        $assetsDir = dirname(base_path(), 2).DIRECTORY_SEPARATOR.'perfume-palace-checkout'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'assets';

        if (! File::isDirectory($assetsDir)) {
            $this->error("Assets folder not found: {$assetsDir}");

            return self::FAILURE;
        }

        Storage::disk('public')->makeDirectory('products');

        $map = [
            'golden-oud' => 'perfume-1.jpg',
            'rose-velvet' => 'perfume-2.jpg',
            'midnight-sapphire' => 'perfume-3.jpg',
            'royal-amber' => 'perfume-4.jpg',
            'fresh-citron' => 'perfume-5.jpg',
            'pearl-mist' => 'perfume-6.jpg',
        ];

        foreach ($map as $slug => $filename) {
            $storagePath = "products/{$slug}.jpg";
            $source = $assetsDir.DIRECTORY_SEPARATOR.$filename;

            if (! File::exists($source)) {
                $this->warn("Missing source: {$filename}");

                continue;
            }

            File::copy($source, Storage::disk('public')->path($storagePath));
            $this->info("Copied {$storagePath}");

            ProductImage::query()
                ->whereHas('product', fn ($q) => $q->where('slug', $slug))
                ->where('is_primary', true)
                ->update(['image_url' => $storagePath]);
        }

        $this->info('Done. Run: php artisan storage:link');

        return self::SUCCESS;
    }
}
