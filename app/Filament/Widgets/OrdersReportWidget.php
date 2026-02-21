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
            $canViewAll = $user->can('ViewAll:Order');
            $isClient = $user->can('ViewOwn:Order') && !$canViewAll;
            $isShipper = $user->can('ViewAssigned:Order') && !$canViewAll;

            // Base query - use 'order' table explicitly if needed, but model is fine
            $query = Order::query();
            
            // Only filter if they DON'T have permission to view all
            if (!$canViewAll) {
                $query->where(function ($q) use ($user, $isClient, $isShipper) {
                    if ($isClient && $isShipper) {
                        $q->where('client_id', $user->id)->orWhere('shipper_id', $user->id);
                    } elseif ($isClient) {
                        $q->where('client_id', $user->id);
                    } elseif ($isShipper) {
                        $q->where('shipper_id', $user->id);
                    } else {
                        // If no specific role but can view widget, maybe they see nothing? 
                        // Let's at least not break the query.
                    }
                });
            }

            // Statuses (Handling common misspellings)
            $deliveredStatus = ['deliverd', 'delivered', 'Delivered', 'تم التسليم'];
            $undeliveredStatus = ['undelivered', 'not delivered', 'لم يتم التسليم'];
            $holdStatus = ['hold', 'pending', 'تأجيل'];
            $outForDeliveryStatus = ['out for delivery', 'shipping', 'في الطريق'];

            // Trend Data - Single query for all trends in the last 30 days
            $getTrendData = function($statusGroup = null) use ($query) {
                $trendQuery = (clone $query)
                    ->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date');
                
                if ($statusGroup) {
                    $trendQuery->whereIn('status', $statusGroup);
                }
                
                $data = $trendQuery->pluck('count', 'date')->toArray();
                
                return collect(range(29, 0))->map(function($days) use ($data) {
                    $date = now()->subDays($days)->format('Y-m-d');
                    return $data[$date] ?? 0;
                })->toArray();
            };

            // Get counts
            $allOrders = (clone $query)->count();
            $outForDelivery = (clone $query)->whereIn('status', $outForDeliveryStatus)->count();
            $hold = (clone $query)->whereIn('status', $holdStatus)->count();
            $delivered = (clone $query)->whereIn('status', $deliveredStatus)->count();
            $undelivered = (clone $query)->whereIn('status', $undeliveredStatus)->count();

            // Trends
            $allOrdersTrend = $getTrendData();
            $deliveredTrend = $getTrendData($deliveredStatus);
            $undeliveredTrend = $getTrendData($undeliveredStatus);

            // Totals
            $outForDeliveryTotal = (clone $query)->whereIn('status', $outForDeliveryStatus)->sum('total_amount');
            $holdTotal = (clone $query)->whereIn('status', $holdStatus)->sum('total_amount');
            $deliveredTotal = (clone $query)->whereIn('status', $deliveredStatus)->sum('total_amount');
            $undeliveredTotal = (clone $query)->whereIn('status', $undeliveredStatus)->sum('total_amount');

            // Financials
            $totalFees = (clone $query)->sum('fees');
            $totalShipperFees = (clone $query)->sum('shipper_fees');
            $totalCOP = (clone $query)->sum('cop');
            $totalRevenue = (clone $query)->whereIn('status', $deliveredStatus)->sum('total_amount');
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
                    ->chart([7, 10, 5, 20, 15, 25, 30])
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
