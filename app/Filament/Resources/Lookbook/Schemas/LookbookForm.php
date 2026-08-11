<?php

namespace App\Filament\Resources\Lookbook\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LookbookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Lookbook Info')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                            TextInput::make('slug')
                                ->required()
                                ->unique('lookbooks', 'slug', ignoreRecord: true),

                            TextInput::make('subtitle')
                                ->nullable(),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                    ]),

                Section::make('Model Image')
                    ->schema([
                        FileUpload::make('model_image')
                            ->label('Model Image')
                            ->image()
                            ->disk('public')
                            ->required(),

                        TextInput::make('model_alt')
                            ->label('Image Alt Text')
                            ->nullable(),
                    ]),

                Section::make('Products')
                    ->schema([
                        Repeater::make('lookbookProducts')
                            ->relationship('lookbookProducts')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} (SKU: {$record->sku}) - " . ($record->category ? $record->category->name : 'No Category'))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required(),
                            ])
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->createItemButtonLabel('Add Product to Look')
                            ->grid(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
