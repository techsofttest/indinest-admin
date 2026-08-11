<?php

namespace App\Filament\Resources\Lookbook\Pages;

use App\Filament\Resources\LookbookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLookbooks extends ListRecords
{
    protected static string $resource = LookbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
