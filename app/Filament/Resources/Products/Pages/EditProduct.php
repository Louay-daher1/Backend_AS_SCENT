<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Concerns\SyncsPrimaryProductImage;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use SyncsPrimaryProductImage;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['primary_image_upload'] = $this->record->primaryImage?->storagePath();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['primary_image_upload']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncPrimaryImageOnSave();
    }
}
