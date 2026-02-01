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
        return false; // Disabled temporarily for cleaner dashboard layout
        
        try {
            $user = auth()->user();
            if (!$user) return false;
            
            // Check if admin
            return $user->isAdmin();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
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
            Stat::make('📦 All Orders', $allOrders)
                ->description('Total')
                ->color('primary'),
            
            Stat::make('🚚 Out for Delivery', $outForDelivery)
                ->description(number_format($outForDeliveryTotal, 2) . ' EGP')
                ->color('info'),
            
            Stat::make('⏸️ Hold', $hold)
                ->description(number_format($holdTotal, 2) . ' EGP')
                ->color('warning'),
            
            Stat::make('✅ Delivered', $delivered)
                ->description(number_format($deliveredTotal, 2) . ' EGP')
                ->color('success'),
            
            Stat::make('❌ Undelivered', $undelivered)
                ->description(number_format($undeliveredTotal, 2) . ' EGP')
                ->color('danger'),

            // Row 2: Shipper collection
            Stat::make('📦 Uncollected Shipper', $unCollectedShipper)
                ->description(number_format($unCollectedShipperTotal, 2) . ' EGP')
                ->color('warning'),
            
            Stat::make('✅ Collected Shipper', $collectedShipper)
                ->description(number_format($collectedShipperTotal, 2) . ' EGP')
                ->color('success'),
            
            Stat::make('📤 Unreturned Shipper', $unReturnShipper)
                ->description(number_format($unReturnShipperTotal, 2) . ' EGP')
                ->color('gray'),
            
            Stat::make('↩️ Returned Shipper', $returnShipper)
                ->description(number_format($returnShipperTotal, 2) . ' EGP')
                ->color('info'),

            Stat::make('💰 Uncollected Client', $unCollectedClient)
                ->description(number_format($unCollectedClientTotal, 2) . ' EGP')
                ->color('warning'),

            // Row 3: Client collection
            Stat::make('✅ Collected Client', $collectedClient)
                ->description(number_format($collectedClientTotal, 2) . ' EGP')
                ->color('success'),
            
            Stat::make('↩️ Returned Client', $returnClient)
                ->description(number_format($returnClientTotal, 2) . ' EGP')
                ->color('info'),
            
            Stat::make('📤 Unreturned Client', $unReturnClient)
                ->description(number_format($unReturnClientTotal, 2) . ' EGP')
                ->color('gray'),

            // Row 4: Financial Summary
            Stat::make('💵 Total Fees', number_format($totalFees, 2) . ' EGP')
                ->description('Fees')
                ->color('primary'),
            
            Stat::make('🚚 Shipper Fees', number_format($totalShipperFees, 2) . ' EGP')
                ->description('Shipper Fees')
                ->color('info'),

            // Row 5: Profits
            Stat::make('💰 Net Profit', number_format($netProfit, 2) . ' EGP')
                ->description('From collected orders')
                ->color('success'),
            
            Stat::make('📊 Total Revenue', number_format($totalRevenue, 2) . ' EGP')
                ->description('From delivered orders')
                ->color('success'),
            
            Stat::make('💸 Expenses', number_format($totalExpenses, 2) . ' EGP')
                ->description('Shipper fees + expenses')
                ->color('danger'),
        ];
        } catch (\Exception $e) {
            return [
                Stat::make('⚠️ Error', 'Failed to load data')
                    ->description($e->getMessage())
                    ->color('danger'),
            ];
        }
    }
}
