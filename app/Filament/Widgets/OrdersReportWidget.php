<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersReportWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 5;
    }

    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('ViewWidget:OrdersReport');
    }

    protected function getStats(): array
    {
        try {
            $user = auth()->user();
            if (!$user) return [];
            
            $isClient = $user->isClient();
            $isShipper = $user->isShipper();

            // Base query
            $query = Order::query()->whereNotNull('status');
            
            if ($isClient) {
                $query->where('client_id', $user->id);
            }
            if ($isShipper) {
                $query->where('shipper_id', $user->id);
            }

            // Trend Data (Last 7 days)
            $getTrend = function($subQuery) {
                return collect(range(6, 0))->map(function($days) use ($subQuery) {
                    return (clone $subQuery)->whereDate('created_at', now()->subDays($days))->count();
                })->toArray();
            };

            // Get counts
            $allOrders = (clone $query)->count();
            $outForDelivery = (clone $query)->where('status', 'out for delivery')->count();
            $hold = (clone $query)->where('status', 'hold')->count();
            $delivered = (clone $query)->where('status', 'deliverd')->count();
            $undelivered = (clone $query)->where('status', 'undelivered')->count();

            // Trends
            $allOrdersTrend = $getTrend(clone $query);
            $deliveredTrend = $getTrend((clone $query)->where('status', 'deliverd'));
            $undeliveredTrend = $getTrend((clone $query)->where('status', 'undelivered'));

            // Totals
            $outForDeliveryTotal = (clone $query)->where('status', 'out for delivery')->sum('total_amount');
            $holdTotal = (clone $query)->where('status', 'hold')->sum('total_amount');
            $deliveredTotal = (clone $query)->where('status', 'deliverd')->sum('total_amount');
            $undeliveredTotal = (clone $query)->where('status', 'undelivered')->sum('total_amount');

            // Financials
            $totalFees = (clone $query)->sum('fees');
            $totalShipperFees = (clone $query)->sum('shipper_fees');
            $totalCOP = (clone $query)->sum('cop');
            $totalRevenue = (clone $query)->where('status', 'deliverd')->sum('total_amount');
            $additionalExpenses = Expense::sum('amount');
            $totalExpenses = $totalShipperFees + $additionalExpenses;
            $netProfit = $totalCOP - $additionalExpenses;

            $currency = ' ' . __('statuses.currency');

            return [
                // Highlight Stats with Charts
                Stat::make(__('app.total_orders'), number_format($allOrders))
                    ->description(__('app.stats_descriptions.total'))
                    ->descriptionIcon('heroicon-m-shopping-cart')
                    ->chart($allOrdersTrend)
                    ->color('primary'),

                Stat::make(__('app.delivered'), number_format($delivered))
                    ->description(number_format($deliveredTotal, 2) . $currency)
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->chart($deliveredTrend)
                    ->color('success'),

                Stat::make(__('app.undelivered'), number_format($undelivered))
                    ->description(number_format($undeliveredTotal, 2) . $currency)
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->chart($undeliveredTrend)
                    ->color('danger'),

                Stat::make(__('app.out_for_delivery'), number_format($outForDelivery))
                    ->description(number_format($outForDeliveryTotal, 2) . $currency)
                    ->descriptionIcon('heroicon-m-truck')
                    ->color('info'),

                Stat::make(__('app.hold'), number_format($hold))
                    ->description(number_format($holdTotal, 2) . $currency)
                    ->descriptionIcon('heroicon-m-pause-circle')
                    ->color('warning'),

                // Financial Overview
                Stat::make(__('app.net_profit'), number_format($netProfit, 2) . $currency)
                    ->description(__('app.stats_descriptions.from_collected'))
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->chart([7, 10, 5, 20, 15, 25, 30]) // Static trend for visual flair if dynamic profit trend is heavy
                    ->color('success'),

                Stat::make(__('app.total_revenue'), number_format($totalRevenue, 2) . $currency)
                    ->description(__('app.stats_descriptions.from_delivered'))
                    ->descriptionIcon('heroicon-m-presentation-chart-line')
                    ->color('success'),

                Stat::make(__('app.total_fees'), number_format($totalFees, 2) . $currency)
                    ->description(__('orders.shipping_fees'))
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),

                Stat::make(__('app.shipper_fees'), number_format($totalShipperFees, 2) . $currency)
                    ->description(__('orders.shipper_commission'))
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('info'),

                Stat::make(__('app.expenses_label'), number_format($totalExpenses, 2) . $currency)
                    ->description(__('app.stats_descriptions.shipper_expenses'))
                    ->descriptionIcon('heroicon-m-receipt-refund')
                    ->color('danger'),
            ];
        } catch (\Exception $e) {
            return [
                Stat::make(__('statuses.error'), __('app.data_load_error'))
                    ->description($e->getMessage())
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
}
