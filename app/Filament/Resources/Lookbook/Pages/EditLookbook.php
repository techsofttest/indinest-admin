<?php

namespace App\Filament\Resources\Lookbook\Pages;

use App\Filament\Resources\LookbookResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLookbook extends EditRecord
{
    protected static string $resource = LookbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
