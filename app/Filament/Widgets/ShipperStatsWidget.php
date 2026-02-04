<?php

namespace App\Filament\Widgets;

use App\Models\CollectedShipper;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipperStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    // تأخير التحميل لعدم التأثير على الأداء
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('ViewWidget:ShipperStats');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        $isShipper = $user->can('ViewAssigned:Order') && !$user->can('ViewAll:Order');

        // استعلام التحصيلات
        $collectionQuery = CollectedShipper::query();
        // استعلام الأوردرات (لحساب العمولات وغيرها)
        $orderQuery = Order::query()->whereNotNull('status');

        if ($isShipper) {
            $collectionQuery->where('shipper_id', $user->id);
            $orderQuery->where('shipper_id', $user->id);
        }

        // 1. تحصيلات معلقة
        $pendingCount = (clone $collectionQuery)->where('status', 'pending')->count();
        $pendingAmount = (clone $orderQuery)->where('collected_shipper', false)->where('status', 'deliverd')->sum('total_amount');

        // 2. تم التحصيل
        $collectedCount = (clone $collectionQuery)->where('status', 'collected')->count();
        $collectedAmount = (clone $orderQuery)->where('collected_shipper', true)->sum('total_amount');
        
        // 3. عمولة المندوب (Shipper Fees)
        $totalFees = (clone $orderQuery)->where('status', 'deliverd')->sum('shipper_fees');

        return [
            Stat::make(__('app.pending_collections'), number_format($pendingAmount, 2) . ' ' . __('statuses.currency'))
                ->description($pendingCount . ' ' . __('app.stats_descriptions.pending_req'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([5, 2, 8, 4, 3, 5, 2]),

            Stat::make(__('app.collected_amount'), number_format($collectedAmount, 2) . ' ' . __('statuses.currency'))
                ->description($collectedCount . ' ' . __('app.stats_descriptions.completed_req'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([2, 5, 3, 8, 4, 9, 10]),

            Stat::make(__('app.total_commission'), number_format($totalFees, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.earnings'))
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary')
                ->chart([1, 2, 3, 4, 5, 4, 6]),
        ];
    }
}
