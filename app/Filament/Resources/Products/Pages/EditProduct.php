<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription(fn (\App\Models\Product $record) => $record->orderItems()->exists()
                    ? "Product has been ordered previously. It will be archived instead of permanently deleted. Historical orders will remain unchanged."
                    : "Are you sure you want to delete this product? It will be removed from the storefront."),
            \Filament\Actions\RestoreAction::make(),
        ];
    }
}
