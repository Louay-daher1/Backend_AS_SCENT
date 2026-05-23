<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Concerns\SyncsPrimaryProductImage;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use SyncsPrimaryProductImage;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['primary_image_upload']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPrimaryImageOnCreate();
    }
}
