<?php

namespace App\Providers;

use App\Models\ProductImage;
use App\Observers\ProductImageObserver;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component->disk('public')->visibility('public');
        });

        JsonResource::withoutWrapping();

        ProductImage::observe(ProductImageObserver::class);
    }
}
