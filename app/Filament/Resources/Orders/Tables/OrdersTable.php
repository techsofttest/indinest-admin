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
                TextColumn::make('assignedStaff.name')
                    ->label('Assigned Staff')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->searchable(),
                TextColumn::make('delivery_type')
                    ->badge()
                    ->color(fn ($state): string => $state === 'direct' ? 'success' : 'gray'),
                TextColumn::make('shipping_postcode')
                    ->label('Postcode')
                    ->searchable(),
                TextColumn::make('grand_total')
                    ->money('AUD')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        return match ($value) {
                            'pending' => 'warning',
                            'paid' => 'success',
                            'failed' => 'danger',
                            default => 'gray',
                        };
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
                            'out_for_delivery' => 'primary',
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('assigned_staff_id')
                    ->label('Assigned Staff')
                    ->options(function () {
                        return \App\Models\User::where('role', 'staff')
                            ->orWhereHas('roles', fn ($q) => $q->where('name', 'Staff'))
                            ->orWhereHas('permissions', fn ($q) => $q->where('name', 'delivery.driver'))
                            ->pluck('name', 'id');
                    }),
                \Filament\Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned Orders')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNull('assigned_staff_id')),
            ])
            ->recordActions([
                ViewAction::make(),
                \Filament\Actions\Action::make('assignStaff')
                    ->label('Assign Staff')
                    ->icon('heroicon-o-user')
                    ->visible(fn () => auth()->user()?->can('orders.assign') ?? false)
                    ->form([
                        \Filament\Forms\Components\Select::make('assigned_staff_id')
                            ->label('Staff Member')
                            ->options(function () {
                                return \App\Models\User::where('role', 'staff')
                                    ->orWhereHas('roles', fn ($q) => $q->where('name', 'Staff'))
                                    ->orWhereHas('permissions', fn ($q) => $q->where('name', 'delivery.driver'))
                                    ->pluck('name', 'id');
                            })
                            ->placeholder('Select a staff member')
                            ->required(),
                    ])
                    ->action(function (\App\Models\Order $record, array $data): void {
                        $record->update([
                            'assigned_staff_id' => $data['assigned_staff_id'],
                            'assigned_at' => now(),
                            'assigned_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulkAssignStaff')
                        ->label('Assign Staff')
                        ->icon('heroicon-o-user')
                        ->visible(fn () => auth()->user()?->can('orders.assign') ?? false)
                        ->form([
                            \Filament\Forms\Components\Select::make('assigned_staff_id')
                                ->label('Staff Member')
                                ->options(function () {
                                    return \App\Models\User::where('role', 'staff')
                                        ->orWhereHas('roles', fn ($q) => $q->where('name', 'Staff'))
                                        ->orWhereHas('permissions', fn ($q) => $q->where('name', 'delivery.driver'))
                                        ->pluck('name', 'id');
                                })
                                ->placeholder('Select a staff member')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'assigned_staff_id' => $data['assigned_staff_id'],
                                    'assigned_at' => now(),
                                    'assigned_by' => auth()->id(),
                                ]);
                            });
                        }),
                ]),
            ]);
    }
}
