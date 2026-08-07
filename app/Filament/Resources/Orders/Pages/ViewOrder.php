<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
    
    protected string $view = 'filament.resources.orders.pages.order-detail';

    public function togglePicked($itemId)
    {
        $item = \App\Models\OrderItem::find($itemId);
        if ($item) {
            $item->update([
                'is_picked' => !$item->is_picked
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            //EditAction::make(),
        ];
    }
}
