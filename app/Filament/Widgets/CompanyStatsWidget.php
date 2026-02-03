<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('View:CompanyStats');
    }

    protected function getStats(): array
    {
        // 1. إيرادات الشركة (Total Fees)
        $totalFees = Order::where('status', 'deliverd')->sum('fees');

        // 2. مصاريف الشحن (Shipper Fees) + مصاريف إضافية (Expenses)
        $totalShipperFees = Order::where('status', 'deliverd')->sum('shipper_fees');
        $additionalExpenses = Expense::sum('amount');
        $totalExpenses = $totalShipperFees + $additionalExpenses;

        // 3. صافي الربح (Net Profit) = COP - Expenses
        // أو Fees - Expenses
        // المعادلة الأدق: COP (من الجدول) - Expenses
        // بس COP في الداتابيز ممكن يكون فيه تفاصيل، خلينا نستخدم المعادلة البسيطة:
        // صافي الربح من الأوردرات (Fees - Shipper Fees) - Expenses
        
        $netProfitFromOrders = $totalFees - $totalShipperFees;
        $netProfit = $netProfitFromOrders - $additionalExpenses;

        return [
            Stat::make(__('app.company_revenue'), number_format($totalFees, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.earnings'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([10, 15, 12, 18, 20, 15, 22]),

            Stat::make(__('app.total_expenses'), number_format($totalExpenses, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.shippers') . ' + ' . __('app.finance'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger')
                ->chart([5, 8, 6, 9, 7, 8, 6]),

            Stat::make(__('app.net_profit'), number_format($netProfit, 2) . ' ' . __('statuses.currency'))
                ->description(__('app.stats_descriptions.after_expenses'))
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('primary')
                ->chart([5, 7, 6, 9, 13, 7, 16]),
        ];
    }
}
