<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListUndeliveredOrders;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

use BackedEnum;
use UnitEnum;
use Filament\Panel;

class UndeliveredOrderResource extends OrderResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-x-circle';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة الطلبات';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->can('ViewAny:Order');
    }

    public static function getNavigationLabel(): string
    {
        return 'أوردرات لم تسلم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أوردرات لم تسلم';
    }

    public static function getModelLabel(): string
    {
        return 'أوردر لم يسلم';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'undelivered-orders';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('order.status', 'undelivered');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user) return null;

        $query = Order::where('status', 'undelivered');
        
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
            'index' => ListUndeliveredOrders::route('/'),
        ];
    }
}
