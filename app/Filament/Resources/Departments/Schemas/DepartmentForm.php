<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
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
                                ->required(),

                            Select::make('parent_id')
                                ->label('Parent Department')
                                ->options(function (?\App\Models\Department $record) {
                                    $query = \App\Models\Department::query();
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    return $query->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload(),
                        ])->columnSpanFull(),

                        Grid::make(1)->schema([

                            FileUpload::make('image')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->saveUploadedFileUsing(fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) => \App\Traits\ProcessesImageUploads::convertToWebpAndCompress($file, 'departments')),

                            /*Textarea::make('description')
                                ->label('Description')
                                ->rows(4)
                                ->columnSpanFull(),*/
                        ])->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }
}