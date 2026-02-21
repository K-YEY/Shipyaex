<?php

namespace App\Filament\Resources\PlanPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlanPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('app.plan'))
                    ->visible(fn () => auth()->user()->can('ViewPlanColumn:PlanPrice'))
                    ->searchable(),
                TextColumn::make('governorate.name')
                    ->label(__('app.governorate'))
                    ->visible(fn () => auth()->user()->can('ViewLocationColumn:PlanPrice'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label(__('app.price'))
                    ->visible(fn () => auth()->user()->can('ViewPriceColumn:PlanPrice'))
                    ->numeric(),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:PlanPrice'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('app.date'))
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:PlanPrice'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label(__('app.plan'))
                    ->relationship('plan', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('Update:PlanPrice')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('DeleteAny:PlanPrice')),
                ]),
            ]);
    }
}
