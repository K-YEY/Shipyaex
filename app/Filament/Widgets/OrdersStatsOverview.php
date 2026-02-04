<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\CollectedShipper;
use App\Models\CollectedClient;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 5;


    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('ViewWidget:OrdersStatsOverview');
    }

    protected function getStats(): array
    {
        try {
            $stats = [];
            $ordersUrl = '/admin/orders';
            $user = auth()->user();
            
            if (!$user) return [];
            
            // تحديد الفلترة حسب الـ Permissions
            $isClient = $user->can('ViewOwn:Order');
            $isShipper = $user->can('ViewAssigned:Order');
            $isAdmin = $user->can('ViewAll:Order'); // Has power to see all
            
            // بناء الـ base query حسب الـ Permissions
            $orderQuery = Order::whereNotNull('status');
            
            if ($isClient && !$isAdmin) {
                $orderQuery->where('client_id', $user->id);
            } elseif ($isShipper && !$isAdmin) {
                $orderQuery->where('shipper_id', $user->id);
            }
            // If it's an admin, no specific filtering by client_id or shipper_id is needed,
            // as they should see all orders. The query remains as is.

        // إجمالي الأوردرات
        $totalOrders = (clone $orderQuery)->count();
        $stats[] = Stat::make(__('app.total_orders'), $totalOrders)
            ->description($isClient ? __('app.stats_descriptions.your_orders') : ($isShipper ? __('app.stats_descriptions.assigned_orders') : __('app.stats_descriptions.all_system')))
            ->descriptionIcon('heroicon-m-inbox-stack')
            ->chart([7, 2, 10, 3, 15, 4, 17])
            ->color('primary')
            ->url($ordersUrl);

        // Out for Delivery
        $outForDelivery = (clone $orderQuery)->where('status', 'out for delivery')->count();
        $stats[] = Stat::make(__('app.out_for_delivery'), $outForDelivery)
            ->description($isShipper ? __('app.stats_descriptions.with_you') : __('app.shippers'))
            ->descriptionIcon('heroicon-m-truck')
            ->chart([15, 4, 10, 2, 12, 4, 12])
            ->color('info')
            ->url($ordersUrl . '?' . http_build_query(['tableFilters' => ['status' => ['value' => 'out for delivery']]]));

        // Delivered
        $delivered = (clone $orderQuery)->where('status', 'deliverd')->count();
        $stats[] = Stat::make(__('app.delivered'), $delivered)
            ->description(__('app.stats_descriptions.success_delivery'))
            ->descriptionIcon('heroicon-m-check-circle')
            ->chart([17, 16, 14, 15, 14, 13, 12])
            ->color('success')
            ->url($ordersUrl . '?' . http_build_query(['tableFilters' => ['status' => ['value' => 'deliverd']]]));

        // لم يDelivered
        $undelivered = (clone $orderQuery)->where('status', 'undelivered')->count();
        $stats[] = Stat::make(__('app.undelivered'), $undelivered)
            ->description(__('app.stats_descriptions.failed_delivery'))
            ->descriptionIcon('heroicon-m-x-circle')
            ->chart([1, 2, 3, 2, 1, 2, 1])
            ->color('danger')
            ->url($ordersUrl . '?' . http_build_query(['tableFilters' => ['status' => ['value' => 'undelivered']]]));

        // Hold
        $hold = (clone $orderQuery)->where('status', 'hold')->count();
        $stats[] = Stat::make(__('app.hold'), $hold)
            ->description(__('app.stats_descriptions.on_hold'))
            ->descriptionIcon('heroicon-m-pause-circle')
            ->chart([5, 4, 10, 2, 12, 4, 2])
            ->color('warning')
            ->url($ordersUrl . '?' . http_build_query(['tableFilters' => ['status' => ['value' => 'hold']]]));
            
        return $stats;
        } catch (\Exception $e) {
            return [
                Stat::make('⚠️ Error', __('app.data_load_error'))
                    ->description($e->getMessage())
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
}
