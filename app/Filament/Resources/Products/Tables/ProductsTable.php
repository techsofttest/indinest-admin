<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Imports\ProductImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([

                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->disk('public')
                    ->size(50)
                    ->square()
                    ->visibility('public'),

                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->state(fn ($record) => $record->category?->department?->name)
                    ->sortable(false)
                    ->searchable(),

                /*TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants')
                    ->sortable(),*/

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                ToggleColumn::make('is_featured')
                    ->label('Featured')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category.department_id')
                    ->label('Department')
                    ->relationship('category.department', 'name')
                    ->searchable()
                    ->preload(),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->headerActions([
                \Filament\Actions\Action::make('import_products')
                    ->label('Import Products')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Excel / CSV File')
                            ->required()
                            ->disk('local')
                            ->directory('imports'),
                        \Filament\Forms\Components\FileUpload::make('zip')
                            ->label('Images ZIP (Optional)')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                            ->disk('local')
                            ->directory('imports'),
                    ])
                    ->action(function (array $data) {
                        $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                        $imageService = null;

                        try {
                            if (!empty($data['zip'])) {
                                $zipPath = \Illuminate\Support\Facades\Storage::disk('local')->path($data['zip']);
                                $imageService = app(\App\Services\Import\ImageImportService::class);
                                $lookup = $imageService->extractZip($zipPath);
                                app(\App\Services\Import\ImageResolver::class)->setLookup($lookup);
                            }

                            $import = new \App\Imports\ProductExcelImport();
                            \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);

                            $summary = $import->getService()->getLogger()->getFormattedSummary();

                            \Filament\Notifications\Notification::make()
                                ->title('Import Completed')
                                ->body($summary)
                                ->success()
                                ->send();
                        } finally {
                            if ($imageService) {
                                $imageService->cleanup();
                            }
                            // Delete uploaded temp files
                            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($data['file'])) {
                                \Illuminate\Support\Facades\Storage::disk('local')->delete($data['file']);
                            }
                            if (!empty($data['zip']) && \Illuminate\Support\Facades\Storage::disk('local')->exists($data['zip'])) {
                                \Illuminate\Support\Facades\Storage::disk('local')->delete($data['zip']);
                            }
                        }
                    })
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

