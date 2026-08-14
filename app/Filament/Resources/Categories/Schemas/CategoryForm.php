<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Models\Department;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Category Information')
                    ->schema([

                        Grid::make(1)->schema([

                            Select::make('department_id')
                                ->label('Department')
                                ->options(Department::query()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('parent_id')
                                ->label('Parent Category')
                                ->options(function ($record) {
                                    $query = Category::whereNull('parent_id');
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    return $query->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder('None (Top-level category)'),

                            TextInput::make('name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('slug', Str::slug($state));
                                }),

                            TextInput::make('slug')
                                ->hidden()
                                ->unique(ignoreRecord: true)
                                ->dehydrated()
                        ]),

                        /*Textarea::make('description')
                            ->default(null)
                            ->columnSpanFull(),*/

                        FileUpload::make('image')
                            ->image()
                            ->disk('public')
                            ->saveUploadedFileUsing(fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) => \App\Traits\ProcessesImageUploads::convertToWebpAndCompress($file, 'categories')),
                        
                    ])->columnSpanFull(),

                Section::make('SEO Settings')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')
                            ->default(null),
                        Textarea::make('meta_description')
                            ->default(null)
                            ->columnSpanFull()
                    ])->columnSpanFull(),
            ]);
    }
}
