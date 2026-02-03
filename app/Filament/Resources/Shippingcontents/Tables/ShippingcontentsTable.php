<?php

namespace App\Filament\Resources\Shippingcontents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingcontentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label(__('app.name'))
                    // 1. Column Permission
                    ->visible(fn () => auth()->user()->can('ViewNameColumn:ShippingContent')),
            ])
            ->filters([
                //
            ])
            ->actions([
                // 2. Row Action Permission (Shield handles Edit/Delete usually via policy, but we can be explicit)
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('Update:ShippingContent')),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('Delete:ShippingContent')),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                    
                    // 3. Custom Action Permission
                    \Filament\Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn() => null) // Logic goes here
                        ->visible(fn() => auth()->user()->can('ExportData:ShippingContent')),
                ]),
            ]);
    }
}
