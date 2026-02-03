<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;

class SettingsTable
{
    public static function getColumns(): array
    {
        return [
            TextColumn::make('key')
                ->label('المفتاح')
                ->visible(fn () => auth()->user()->can('ViewKeyColumn:Setting'))
                ->searchable()
                ->sortable()
                ->copyable()
                ->badge()
                ->color('primary'),
            
            TextInputColumn::make('value')
                ->label('القيمة')
                ->visible(fn () => auth()->user()->can('ViewValueColumn:Setting'))
                ->disabled(fn () => !auth()->user()->can('EditValueField:Setting'))
                ->searchable()
                ->rules(['required']),
            
            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->visible(fn () => auth()->user()->can('ViewDatesColumn:Setting'))
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            
            TextColumn::make('updated_at')
                ->label('آخر تحديث')
                ->visible(fn () => auth()->user()->can('ViewDatesColumn:Setting'))
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->since(),
        ];
    }

    public static function getFilters(): array
    {
        return [
            //
        ];
    }

    public static function getActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل')
                ->visible(fn () => auth()->user()->can('Update:Setting')),
            DeleteAction::make()
                ->label('مسح')
                ->visible(fn () => auth()->user()->can('Delete:Setting'))
                ->requiresConfirmation()
                ->modalHeading('مسح الإعداد')
                ->modalDescription('متأكد إنك عاوز تمسح الإعداد ده؟ الخطوة دي مش هينفع ترجع فيها.'),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->label('مسح المختار')
                    ->visible(fn () => auth()->user()->can('DeleteAny:Setting'))
                    ->requiresConfirmation(),
            ]),
        ];
    }
}
