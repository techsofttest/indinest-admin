<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Lookbook\Pages\CreateLookbook;
use App\Filament\Resources\Lookbook\Pages\EditLookbook;
use App\Filament\Resources\Lookbook\Pages\ListLookbooks;
use App\Filament\Resources\Lookbook\Schemas\LookbookForm;
use App\Filament\Resources\Lookbook\Tables\LookbooksTable;
use App\Models\Lookbook;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LookbookResource extends Resource
{
    protected static ?string $model = Lookbook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return LookbookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LookbooksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLookbooks::route('/'),
            'create' => CreateLookbook::route('/create'),
            'edit' => EditLookbook::route('/{record}/edit'),
        ];
    }
}
