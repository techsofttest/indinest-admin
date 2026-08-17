<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id','desc')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->searchable(),
                TextColumn::make('order_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state): string => $state === 'enquiry' ? 'warning' : 'success'),
                TextColumn::make('shipping_method')
                    ->label('Shipping Method')
                    ->badge()
                    ->formatStateUsing(function ($state, \App\Models\Order $record): string {
                        $method = $state ? ucfirst($state) : null;
                        $cost = $record->shipping_cost !== null ? '£' . number_format((float) $record->shipping_cost, 2) : null;
                        
                        if ($method && $cost) {
                            return "{$method} ({$cost})";
                        }
                        
                        return $method ?? $cost ?? 'N/A';
                    })
                    ->color(function ($state): string {
                        $method = strtolower((string) $state);
                        return match ($method) {
                            'express' => 'warning',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('grand_total')
                    ->money('GBP')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        return match ($value) {
                            'pending' => 'warning',
                            'paid' => 'success',
                            'failed' => 'danger',
                            'not_required' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        return $value === 'not_required' ? 'Not Required' : ucfirst($value);
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        return match ($value) {
                            'pending', 'pending_payment' => 'warning',
                            'confirmed' => 'success',
                            'processing' => 'info',
                            'packed' => 'primary',
                            'ready' => 'info',
                            'shipped', 'out_for_delivery' => 'primary',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        return $value === 'pending_payment' ? 'Pending Payment' : ucfirst($value);
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, g:i a')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('order_type')
                    ->label('Type')
                    ->options([
                        'order' => 'Order',
                        'enquiry' => 'Enquiry',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'not_required' => 'Not Required',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                \Filament\Actions\Action::make('markAsShipped')
                    ->label('Mark as Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Shipped')
                    ->modalDescription('Are you sure you want to mark this order as shipped? The customer will be notified by email.')
                    ->visible(fn (\App\Models\Order $record) => 
                        in_array($record->status instanceof \BackedEnum ? $record->status->value : (string) $record->status, ['confirmed', 'processing'], true) &&
                        ($record->order_type === 'enquiry' || ($record->payment_status instanceof \BackedEnum ? $record->payment_status->value : (string) $record->payment_status) === 'paid')
                    )
                    ->action(function (\App\Models\Order $record): void {
                        try {
                            $record->markAsShipped();
                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body('Order has been marked as shipped.')
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
                \Filament\Actions\Action::make('markAsDelivered')
                    ->label('Mark as Delivered')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Delivered')
                    ->modalDescription('Are you sure you want to mark this order as delivered?')
                    ->visible(fn (\App\Models\Order $record) => 
                        ($record->status instanceof \BackedEnum ? $record->status->value : (string) $record->status) === 'shipped'
                    )
                    ->action(function (\App\Models\Order $record): void {
                        try {
                            $record->markAsDelivered();
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
