<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListDeliveredOrders;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

use BackedEnum;
use UnitEnum;
use Filament\Panel;

class DeliveredOrderResource extends OrderResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الطلبات';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->can('ViewAny:Order');
    }

    public static function getNavigationLabel(): string
    {
        return 'أوردرات تم التسليم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أوردرات تم التسليم';
    }

    public static function getModelLabel(): string
    {
        return 'أوردر تم تسليمه';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'delivered-orders';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('order.status', 'deliverd');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) return null;

        $cacheKey = 'nav_badge_delivered_orders_' . $user->id;

        return Cache::remember($cacheKey, 120, function () use ($user) {
            $query = Order::where('status', 'deliverd');
            
            if ($user->isAdmin() || $user->can('ViewAll:Order')) {
                // Admin
            } elseif ($user->isClient() || $user->can('ViewOwn:Order')) {
                $query->where('client_id', $user->id);
            } elseif ($user->isShipper() || $user->can('ViewAssigned:Order')) {
                $query->where('shipper_id', $user->id)
                      ->where('collected_shipper', false);
            } else {
                return 0;
            }

            return $query->count();
        }) ?: null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveredOrders::route('/'),
        ];
    }
}
