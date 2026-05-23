<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image_url',
        'sort_order',
    ];

    public function publicImageUrl(): ?string
    {
        $path = $this->image_url;

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
