<?php

namespace App\Filament\Resources\CollectedShippers;

use App\Filament\Resources\CollectedShippers\Pages\CreateCollectedShipper;
use App\Filament\Resources\CollectedShippers\Pages\EditCollectedShipper;
use App\Filament\Resources\CollectedShippers\Pages\ListCollectedShippers;
use App\Filament\Resources\CollectedShippers\Pages\ViewCollectedShipper;
use App\Filament\Resources\CollectedShippers\Schemas\CollectedShipperForm;
use App\Filament\Resources\CollectedShippers\Tables\CollectedShippersTable;
use App\Models\CollectedShipper;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;

class CollectedShipperResource extends Resource
{
    protected static ?string $model = CollectedShipper::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.collected_shippers');
    }

    public static function getModelLabel(): string
    {
        return 'توريدة';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.collected_shippers');
    }

    /**
     * إخفاء الـ Navigation للعميل (Client No يرى تحصيل Shipperين)
     */
    public static function canViewNavigation(): bool
    {
        $user = auth()->user();
        
        if ($user->isClient()) {
            return false;
        }
        
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الماليات والمتابعة';
    }

    public static function form(Schema $schema): Schema
    {
        return CollectedShipperForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectedShippersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollectedShippers::route('/'),
            'create' => CreateCollectedShipper::route('/create'),
            'view' => ViewCollectedShipper::route('/{record}'),
            'edit' => EditCollectedShipper::route('/{record}/edit'),
        ];
    }

    /**
     * Modify the query based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Admin يرى All
        if ($user->isAdmin()) {
            return $query;
        }

        // Shipper يرى تحصيNoته فقط
        if ($user->isShipper()) {
            return $query->where('shipper_id', $user->id);
        }

        // Users الآخرين No يرون شيء
        return $query->whereRaw('1 = 0');
    }

    /**
     * Badge للعدد في القائمة الجانبية
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return static::getModel()::where('status', 'pending')->count() ?: null;
        }

        if ($user->isShipper()) {
            return static::getModel()::where('shipper_id', $user->id)
                ->where('status', 'pending')
                ->count() ?: null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
