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
                ->searchable()
                ->sortable()
                ->copyable()
                ->badge()
                ->color('primary'),
            
            TextInputColumn::make('value')
                ->label('القيمة')
                ->searchable()
                ->rules(['required']),
            
            TextColumn::make('created_at')
                ->label('تاريخ الإنشاء')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            
            TextColumn::make('updated_at')
                ->label('آخر تحديث')
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
                ->label('تعديل'),
            DeleteAction::make()
                ->label('مسح')
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
                    ->requiresConfirmation(),
            ]),
        ];
    }
}
