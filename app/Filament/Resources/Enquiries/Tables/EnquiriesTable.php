<?php

namespace App\Filament\Resources\Enquiries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('enquiry_number')
                    ->searchable()
                    ->sortable()
                    ->label('Enquiry Number'),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                TextColumn::make('customer_email')
                    ->searchable()
                    ->sortable()
                    ->label('Email'),
                TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->money('GBP')
                    ->sortable()
                    ->label('Total Value'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted At'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
