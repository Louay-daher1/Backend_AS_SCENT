<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Slide */
class SlideResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->publicImageUrl(),
            'route' => $this->route,
            'button_text' => $this->button_text,
            'show_logo' => $this->show_logo,
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
        ];
    }

    private function publicImageUrl(): ?string
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
}
