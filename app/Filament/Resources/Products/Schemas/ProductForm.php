<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\MultiSelect;

use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Department;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /* ================= BASIC INFO ================= */

            Section::make('Basic Information')
                ->schema([

                    Grid::make(3)->schema([

                        Grid::make(4)->schema([

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),

                            Toggle::make('is_featured')
                                ->label('Featured')
                                ->inline(false),

                        ])->columnSpanFull(),

                        Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                $categories = Category::with('children')->whereNull('parent_id')->get();
                                $options = [];
                                foreach ($categories as $parent) {
                                    if ($parent->children->count() > 0) {
                                        foreach ($parent->children as $child) {
                                            $options[$parent->name][$child->id] = $child->name;
                                        }
                                    } else {
                                        $options[$parent->id] = $parent->name;
                                    }
                                }
                                return $options;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $category = Category::find($state);
                                if ($category?->department) {
                                    $set('department_display', $category->department->name);
                                } else {
                                    $set('department_display', null);
                                }
                            }),

                        TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn($state, $set) =>
                                $set('slug', Str::slug($state))
                            ),

                        MultiSelect::make('collections')
                            ->label('Collections')
                            ->relationship('collections', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('sku')
                            ->label('Product SKU'),

                        TextInput::make('slug')
                            ->hidden()
                            ->unique(ignoreRecord: true)
                            ->dehydrated(),

                        Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Brand')

                    ])->columnSpanFull(),

                ])->columnSpanFull(),

            /* ================= VARIANTS (REPEATER) ================= */

            Section::make('Product Variants')
                ->description('Every product must have at least one variant. Prices are set per variant.')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('sku')
                                    ->label('Variant SKU'),

                                TextInput::make('size')
                                    ->label('Variant Name')
                                    ->placeholder('e.g. S, M, Pack of 3, Indigo'),

                                TextInput::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('tax_percentage')
                                    ->label('Tax %')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $buying = floatval($get('buying_price'));
                                        $margin = floatval($get('margin'));
                                        $tax = floatval($state);
                                        if ($buying > 0 && ($margin > 0 || $tax > 0)) {
                                            $priceAfterTax = $buying * (1 + ($tax / 100));
                                            $sellingPrice = $priceAfterTax * (1 + ($margin / 100));
                                            $set('selling_price', round($sellingPrice, 2));
                                        }
                                    }),

                                TextInput::make('buying_price')
                                    ->label('MRP')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('£')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $margin = floatval($get('margin'));
                                        $buying = floatval($state);
                                        if ($buying > 0 && $margin > 0) {
                                            $set('selling_price', round($buying + ($buying * $margin / 100), 2));
                                        }
                                    }),

                                TextInput::make('selling_price')
                                    ->label('Selling Price')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('£'),
                            ]),
                        ])
                        ->addActionLabel('Add Variant')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->reorderable(false)
                        ->collapsible(),
                ])->columnSpanFull(),

            /* ================= MEDIA ================= */

            Section::make('Product Media')
                ->schema([
                    FileUpload::make('featured_image')
                        ->label('Featured Image')
                        ->disk('public')
                        ->image()
                        ->preserveFilenames(),

                ])->columnSpanFull(),

            /* ================= ADDITIONAL IMAGES ================= */

            Section::make('Additional Images')
                ->schema([
                    Repeater::make('images')
                        ->relationship()
                        ->label('Additional Images')
                        ->schema([
                            FileUpload::make('image_path')
                                ->label('Image')
                                ->disk('public')
                                ->image()
                                ->preserveFilenames()
                                ->required(),
                        ])
                        ->addActionLabel('Add Image')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->collapsible(),
                ])->columnSpanFull(),

            /* ================= DETAILS ================= */

            Section::make('Product Details')
                ->schema([

                    RichEditor::make('key_features')
                        ->label('Key Features')
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Product Description')
                        ->columnSpanFull(),

                ])->columnSpanFull(),

            /* ================= SEO ================= */

            Section::make('SEO Settings')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')
                        ->label('SEO Title'),

                    Textarea::make('meta_description')
                        ->label('SEO Description')
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }
}
