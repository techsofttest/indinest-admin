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
            \Filament\Actions\Action::make('markAsDelivered')
                ->label('Mark as Delivered')
                ->color('success')
                ->icon('heroicon-o-truck')
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Delivered')
                ->modalDescription('Are you sure you want to mark this order as delivered?')
                ->visible(fn () => 
                    $this->record->status !== \App\Enums\OrderStatus::DELIVERED &&
                    ($this->record->order_type === 'enquiry' || $this->record->payment_status === \App\Enums\PaymentStatus::PAID)
                )
                ->action(function () {
                    try {
                        $this->record->markAsDelivered();
                        \Filament\Notifications\Notification::make()
                            ->title('Success')
                            ->body('Order has been marked as delivered.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
