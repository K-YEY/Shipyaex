<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\Tables\OrdersTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class ClientOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'clientOrders';

    protected static ?string $title = 'أوردرات العميل';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isClient();
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
