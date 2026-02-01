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
                    ->label('الباقة')
                    ->searchable(),
                TextColumn::make('location_id')
                    ->label('المكان / المحافظة')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->governorate->name;
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan_id')
                    ->label('فلترة بالباقة')
                    ->relationship('plan', 'name'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('مسح المختار'),
                ])->label('عمليات على المختار'),
            ]);
    }
}
