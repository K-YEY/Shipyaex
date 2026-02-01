<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.orders');
    }

    public static function getModelLabel(): string
    {
        return 'أوردر';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.orders');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
        ];
    }

    /**
     * فلترة الأوردرات حسب دور الUser
     * Client: يرى أوردراته فقط
     * Shipper: يرى الأوردرات الموكلة له فقط
     * Admin: يرى كل الأوردرات
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereNotNull('status');
        
        // ⚡ PERFORMANCE OPTIMIZATION: Eager load all relationships to prevent N+1 queries
        // This reduces database queries from 100+ to ~10 for large datasets
        $query->with([
            'client:id,name,phone,email',
            'shipper:id,name,phone,commission',
            'governorate:id,name',
            'city:id,name,governorate_id',
            'collectedShipper:id,status,created_at',
            'collectedClient:id,status,created_at',
            'returnedShipper:id,status,created_at',
        ]);
        
        $user = auth()->user();

        // إذا لم يكن هناك User مسجل
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin / super_admin / panel_user يرى All
        if ($user->isAdmin() || $user->hasRole('panel_user')) {
            return $query;
        }

        // Client يرى أوردراته فقط
        if ($user->isClient()) {
            return $query->where('client_id', $user->id);
        }

        // Shipper يرى الأوردرات الموكلة له فقط
        if ($user->isShipper()) {
            return $query->where('shipper_id', $user->id);
        }

        // Default: أظهر All
        return $query;
    }

    /**
     * Badge للعدد في القائمة الجانبية
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
