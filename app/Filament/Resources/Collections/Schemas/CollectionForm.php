<?php

namespace App\Filament\Resources\Collections\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Collection Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Collection Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),

                        FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public'),
                    ]),
            ]);
    }
}
