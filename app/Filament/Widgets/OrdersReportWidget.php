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

        // Get counts
        $allOrders = (clone $query)->count();
        $outForDelivery = (clone $query)->where('status', 'out for delivery')->count();
        $hold = (clone $query)->where('status', 'hold')->count();
        $delivered = (clone $query)->where('status', 'deliverd')->count();
        $undelivered = (clone $query)->where('status', 'undelivered')->count();

        // Collection stats
        $unCollectedShipper = (clone $query)->where('collected_shipper', false)->count();
        $collectedShipper = (clone $query)->where('collected_shipper', true)->count();
        $unReturnShipper = (clone $query)->where('return_shipper', false)->whereIn('status', ['undelivered'])->count();
        $returnShipper = (clone $query)->where('return_shipper', true)->count();
        
        $unCollectedClient = (clone $query)->where('collected_client', false)->where('collected_shipper', true)->count();
        $collectedClient = (clone $query)->where('collected_client', true)->count();
        $returnClient = (clone $query)->where('return_client', true)->count();
        $unReturnClient = (clone $query)->where('return_client', false)->where('has_return', true)->count();

        // Totals for each category
        $outForDeliveryTotal = (clone $query)->where('status', 'out for delivery')->sum('total_amount');
        $holdTotal = (clone $query)->where('status', 'hold')->sum('total_amount');
        $deliveredTotal = (clone $query)->where('status', 'deliverd')->sum('total_amount');
        $undeliveredTotal = (clone $query)->where('status', 'undelivered')->sum('total_amount');

        $unCollectedShipperTotal = (clone $query)->where('collected_shipper', false)->sum('total_amount');
        $collectedShipperTotal = (clone $query)->where('collected_shipper', true)->sum('total_amount');
        $unReturnShipperTotal = (clone $query)->where('return_shipper', false)->whereIn('status', ['undelivered'])->sum('total_amount');
        $returnShipperTotal = (clone $query)->where('return_shipper', true)->sum('total_amount');
        
        $unCollectedClientTotal = (clone $query)->where('collected_client', false)->where('collected_shipper', true)->sum('cod');
        $collectedClientTotal = (clone $query)->where('collected_client', true)->sum('cod');
        $returnClientTotal = (clone $query)->where('return_client', true)->sum('total_amount');
        $unReturnClientTotal = (clone $query)->where('return_client', false)->where('has_return', true)->sum('total_amount');

        // Financial totals
        $totalFees = (clone $query)->sum('fees');
        $totalShipperFees = (clone $query)->sum('shipper_fees');
        $totalCOP = (clone $query)->sum('cop');
        $totalRevenue = (clone $query)->where('status', 'deliverd')->sum('total_amount');
        $additionalExpenses = Expense::sum('amount');
        $totalExpenses = $totalShipperFees + $additionalExpenses;
        $netProfit = $totalCOP - $additionalExpenses;

        return [
            // Row 1: Status counts
            Stat::make('📦 ' . __('app.total_orders'), $allOrders)
                ->description(__('app.stats_descriptions.total'))
                ->color('primary'),
            
            Stat::make('🚚 ' . __('app.out_for_delivery'), $outForDelivery)
                ->description(number_format($outForDeliveryTotal, 2) . ' ' . __('statuses.currency'))
                ->color('info'),
            
            Stat::make('⏸️ ' . __('app.hold'), $hold)
                ->description(number_format($holdTotal, 2) . ' ' . __('statuses.currency'))
                ->color('warning'),
            
            Stat::make('✅ ' . __('app.delivered'), $delivered)
                ->description(number_format($deliveredTotal, 2) . ' ' . __('statuses.currency'))
                ->color('success'),
            
            Stat::make('❌ ' . __('app.undelivered'), $undelivered)
                ->description(number_format($undeliveredTotal, 2) . ' ' . __('statuses.currency'))
                ->color('danger'),

            // Row 2: Shipper collection
            Stat::make('📦 ' . __('app.uncollected_shipper'), $unCollectedShipper)
                ->description(number_format($unCollectedShipperTotal, 2) . ' ' . __('statuses.currency'))
                ->color('warning'),
            
            Stat::make('✅ ' . __('app.collected_shipper'), $collectedShipper)
                ->description(number_format($collectedShipperTotal, 2) . ' ' . __('statuses.currency'))
                ->color('success'),
            
            Stat::make('📤 ' . __('app.unreturned_shipper'), $unReturnShipper)
                ->description(number_format($unReturnShipperTotal, 2) . ' ' . __('statuses.currency'))
                ->color('gray'),
            
            Stat::make('↩️ ' . __('app.returned_shipper'), $returnShipper)
                ->description(number_format($returnShipperTotal, 2) . ' ' . __('statuses.currency'))
                ->color('info'),

            Stat::make('💰 ' . __('app.uncollected_client'), $unCollectedClient)
                ->description(number_format($unCollectedClientTotal, 2) . ' ' . __('statuses.currency'))
                ->color('warning'),

            // Row 3: Client collection
            Stat::make('✅ ' . __('app.collected_client'), $collectedClient)
                ->description(number_format($collectedClientTotal, 2) . ' ' . __('statuses.currency'))
                ->color('success'),
            
            Stat::make('↩️ ' . __('app.returned_client'), $returnClient)
                ->description(number_format($returnClientTotal, 2) . ' ' . __('statuses.currency'))
                ->color('info'),
            
            Stat::make('📤 ' . __('app.unreturned_client'), $unReturnClient)
                ->description(number_format($unReturnClientTotal, 2) . ' ' . __('statuses.currency'))
                ->color('gray'),

            // Row 4: Financial Summary
            Stat::make('💵 ' . __('app.total_fees'), number_format($totalFees, 2) . ' ' . __('statuses.currency'))
                ->description(__('orders.shipping_fees'))
                ->color('primary'),
            
            Stat::make('🚚 ' . __('app.shipper_fees'), number_format($totalShipperFees, 2) . ' ' . __('statuses.currency'))
                ->description(__('orders.shipper_commission'))
                ->color('info'),

            // Row 5: Profits
            Stat::make('💰 ' . __('app.net_profit'), number_format($netProfit, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.from_collected'))
                ->color('success'),
            
            Stat::make('📊 ' . __('app.total_revenue'), number_format($totalRevenue, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.from_delivered'))
                ->color('success'),
            
            Stat::make('💸 ' . __('app.expenses_label'), number_format($totalExpenses, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.shipper_expenses'))
                ->color('danger'),
        ];
        } catch (\Exception $e) {
            return [
                Stat::make('⚠️ ' . __('statuses.error'), __('app.data_load_error'))
                    ->description($e->getMessage())
                    ->color('danger'),
            ];
        }
    }
}
