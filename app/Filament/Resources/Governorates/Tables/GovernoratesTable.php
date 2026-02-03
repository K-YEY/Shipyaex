<?php

namespace App\Filament\Resources\Governorates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GovernoratesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->can('ViewNameColumn:Governorate'))
                    ->searchable(),
                TextColumn::make('follow_up_hours')
                    ->label(__('app.follow_up_hours'))
                    ->visible(fn () => auth()->user()->can('ViewFollowUpHoursColumn:Governorate'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shipper.name')
                    ->label(__('app.default_shipper'))
                    ->visible(fn () => auth()->user()->can('ViewShipperColumn:Governorate'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:Governorate'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:Governorate'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('Update:Governorate')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('DeleteAny:Governorate')),
                ]),
            ]);
    }
}
