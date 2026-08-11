<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;

class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Enquiry Reference')
                    ->columns(2)
                    ->schema([
                        TextInput::make('enquiry_number')
                            ->disabled()
                            ->label('Enquiry Number'),
                        TextInput::make('created_at')
                            ->disabled()
                            ->label('Submitted At'),
                    ]),

                Section::make('Customer Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->disabled()
                            ->label('Name'),
                        TextInput::make('customer_email')
                            ->disabled()
                            ->label('Email'),
                        TextInput::make('customer_phone')
                            ->disabled()
                            ->label('Phone'),
                    ]),

                Section::make('Delivery Address')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')
                            ->disabled()
                            ->columnSpanFull()
                            ->label('Street Address'),
                        TextInput::make('apartment')
                            ->disabled()
                            ->label('Apartment / Suite'),
                        TextInput::make('city')
                            ->disabled()
                            ->label('City'),
                        TextInput::make('state')
                            ->disabled()
                            ->label('State / Province'),
                        TextInput::make('pin_code')
                            ->disabled()
                            ->label('Postcode / ZIP'),
                        TextInput::make('country')
                            ->disabled()
                            ->label('Country'),
                    ]),

                Section::make('Pricing')
                    ->columns(3)
                    ->schema([
                        TextInput::make('subtotal')
                            ->disabled()
                            ->numeric()
                            ->prefix('£'),
                        TextInput::make('discount')
                            ->disabled()
                            ->numeric()
                            ->prefix('£'),
                        TextInput::make('grand_total')
                            ->disabled()
                            ->numeric()
                            ->prefix('£')
                            ->label('Total Value'),
                    ]),

                Section::make('Requested Items')
                    ->schema([
                        Repeater::make('items')
                            ->disabled()
                            ->columns(5)
                            ->schema([
                                TextInput::make('product_name')
                                    ->disabled()
                                    ->label('Product'),
                                TextInput::make('variant_details')
                                    ->disabled()
                                    ->label('Variant'),
                                TextInput::make('quantity')
                                    ->disabled()
                                    ->numeric()
                                    ->label('Quantity'),
                                TextInput::make('price')
                                    ->disabled()
                                    ->numeric()
                                    ->prefix('£'),
                                TextInput::make('line_total')
                                    ->disabled()
                                    ->numeric()
                                    ->prefix('£'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull()
                            ->label('Checkout Notes'),
                    ]),
            ]);
    }
}
