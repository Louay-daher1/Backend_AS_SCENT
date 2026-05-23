<?php

namespace App\Filament\Resources\Products\Concerns;

use App\Models\ProductImage;

trait SyncsPrimaryProductImage
{
    protected function primaryImageUploadPath(): ?string
    {
        return ProductImage::make()->normalizePath(
            $this->form->getState()['primary_image_upload'] ?? null,
        );
    }

    protected function syncPrimaryImageOnCreate(): void
    {
        $path = $this->primaryImageUploadPath();

        if (! $path) {
            return;
        }

        $this->record->images()->create([
            'image_url' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    protected function syncPrimaryImageOnSave(): void
    {
        $path = $this->primaryImageUploadPath();

        if (! $path) {
            return;
        }

        $primary = $this->record->images()->where('is_primary', true)->first();

        if ($primary) {
            $primary->update(['image_url' => $path]);

            return;
        }

        $this->record->images()->create([
            'image_url' => $path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
