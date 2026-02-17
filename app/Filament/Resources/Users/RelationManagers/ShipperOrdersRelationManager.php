<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\Tables\OrdersTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Model;

class ShipperOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'shipperOrders';

    protected static ?string $title = 'أوردرات المندوب';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isShipper();
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->headerActions([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
