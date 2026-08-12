<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Department Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Department Name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                            TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Departments are displayed in ascending order. Use drag-and-drop in the table to reorder.'),
                        ])->columnSpanFull(),

                        Grid::make(1)->schema([
                            TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true),

                            FileUpload::make('image')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->saveUploadedFileUsing(fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) => \App\Traits\ProcessesImageUploads::convertToWebpAndCompress($file, 'departments')),

                            Textarea::make('description')
                                ->label('Description')
                                ->rows(4)
                                ->columnSpanFull(),
                        ])->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }
}