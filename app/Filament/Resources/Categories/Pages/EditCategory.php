<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function ($record) {
                    if ($record->products()->exists()) {
                        throw ValidationException::withMessages([
                            'category' => 'This category cannot be deleted because it is assigned to one or more products.',
                        ]);
                    }
                }),
        ];
    }
}
