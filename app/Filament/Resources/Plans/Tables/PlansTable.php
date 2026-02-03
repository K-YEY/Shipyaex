<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->can('ViewNameColumn:Plan'))
                    ->searchable(),
                TextColumn::make('order_count')
                    ->label(__('orders.orders'))
                    ->visible(fn () => auth()->user()->can('ViewOrderCountColumn:Plan'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:Plan'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.date'))
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:Plan'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('Update:Plan')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('DeleteAny:Plan')),
                ]),
            ]);
    }
}
