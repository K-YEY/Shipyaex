<?php

namespace App\Filament\Widgets;

use App\Models\CollectedClient;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('ViewOwn:Order') || $user->can('ViewAll:Order');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        $isClient = $user->can('ViewOwn:Order') && !$user->can('ViewAll:Order');

        $collectionQuery = CollectedClient::query();
        $orderQuery = Order::query()->whereNotNull('status');

        if ($isClient) {
            $collectionQuery->where('client_id', $user->id);
            $orderQuery->where('client_id', $user->id);
        }

        // 1. معلق (Pending) - فلوس العميل عند الشركة
        // ده بيبقى COD المحصل من المندوب ولم يتم تحويله للعميل بعد
        $pendingAmount = (clone $orderQuery)
            ->where('collected_shipper', true) // المندوب ورد
            ->where('collected_client', false) // العميل لسه ماخدش
            ->sum('cod');

        $pendingCount = (clone $collectionQuery)->where('status', 'pending')->count();

        // 2. تم التحويل (Collected)
        $collectedAmount = (clone $orderQuery)->where('collected_client', true)->sum('cod');
        $collectedCount = (clone $collectionQuery)->where('status', 'collected')->count();

        // 3. مرتجعات (Returned to Client)
        // أوردرات رجعت للعميل
        $returnedCount = (clone $orderQuery)->where('return_client', true)->count();
        $returnedAmount = (clone $orderQuery)->where('return_client', true)->sum('total_amount'); // قيمة البضاعة المرجعة

        return [
            Stat::make(__('app.client_pending_balance'), number_format($pendingAmount, 2) . ' ' . __('statuses.currency'))
                ->description($pendingCount . ' ' . __('app.stats_descriptions.pending_req'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([3, 5, 2, 6, 3, 5, 4]),

            Stat::make(__('app.paid_to_client'), number_format($collectedAmount, 2) . ' ' . __('statuses.currency'))
                ->description($collectedCount . ' ' . __('app.stats_descriptions.completed_req'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([2, 4, 6, 8, 5, 9, 10]),

            Stat::make(__('app.returned_items'), $returnedCount . ' ' . __('app.orders'))
                ->description(__('app.stats_descriptions.value') . ': ' . number_format($returnedAmount, 2) . ' ' . __('statuses.currency'))
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('info')
                ->chart([1, 0, 2, 1, 0, 1, 3]),
        ];
    }
}
