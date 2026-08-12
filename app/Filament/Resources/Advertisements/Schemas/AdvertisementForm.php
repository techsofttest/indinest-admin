<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('title'),
                FileUpload::make('banner')
                    ->helperText('Approx 1920 x 524 pixels')
                    ->image()
                    ->disk('public')
                    ->saveUploadedFileUsing(fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) => \App\Traits\ProcessesImageUploads::convertToWebpAndCompress($file, 'advertisements'))
                    ->required(),
                TextInput::make('url')
                    ->url()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
