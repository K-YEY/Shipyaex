<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOtherOrders;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

use BackedEnum;
use UnitEnum;
use Filament\Panel;

class OtherOrdersResource extends OrderResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ellipsis-horizontal-circle';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الطلبات';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->can('ViewAny:Order');
    }

    public static function getNavigationLabel(): string
    {
        return 'أوردرات حالات أخرى';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أوردرات حالات أخرى';
    }

    public static function getModelLabel(): string
    {
        return 'أوردر حالة أخرى';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'other-orders';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('order.status', ['deliverd', 'undelivered']);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) return null;

        $query = Order::whereNotIn('status', ['deliverd', 'undelivered']);
        
        if ($user->isAdmin() || $user->can('ViewAll:Order')) {
            $count = $query->count();
        } elseif ($user->isClient() || $user->can('ViewOwn:Order')) {
            $count = $query->where('client_id', $user->id)->count();
        } elseif ($user->isShipper() || $user->can('ViewAssigned:Order')) {
            $count = $query->where('shipper_id', $user->id)
                  ->where('collected_shipper', false)->count();
        } else {
            $count = 0;
        }

        return $count ?: null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOtherOrders::route('/'),
        ];
    }
}
