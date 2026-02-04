<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MonthlyRevenueChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'monthlyRevenueChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected function getHeading(): ?string
    {
        return __('app.company_revenue') . ' & ' . __('app.orders');
    }

    /**
     * Sort order
     */
    protected static ?int $sort = 3;

    /**
     * Widget column span
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * Check if user can view this widget
     */
    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('ViewWidget:MonthlyRevenue');
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        try {
            // Get data for last 12 months
            $months = [];
            $revenue = [];
            $orders = [];
            $profit = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                // Month label
                $months[] = $date->format('M Y');

                // Get revenue (total amount from delivered orders)
                $monthRevenue = Order::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'deliverd')
                    ->sum('total_amount');
                $revenue[] = round($monthRevenue, 2);

                // Get orders count
                $monthOrders = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();
                $orders[] = $monthOrders;

                // Get profit (COP - Company fees)
                $monthProfit = Order::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'deliverd')
                    ->sum('cop');
                $profit[] = round($monthProfit, 2);
            }

            return [
                'chart' => [
                    'type' => 'area',
                    'height' => 350,
                    'toolbar' => [
                        'show' => true,
                    ],
                ],
                'series' => [
                    [
                        'name' => __('app.total_revenue') . ' (' . __('statuses.currency') . ')',
                        'data' => $revenue,
                    ],
                    [
                        'name' => __('app.net_profit') . ' (' . __('statuses.currency') . ')',
                        'data' => $profit,
                    ],
                    [
                        'name' => __('app.total_orders'),
                        'data' => $orders,
                    ],
                ],
                'xaxis' => [
                    'categories' => $months,
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontSize' => '12px',
                        ],
                    ],
                ],
                'yaxis' => [
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                        ],
                    ],
                ],
                'colors' => ['#3b82f6', '#10b981', '#f59e0b'],
                'stroke' => [
                    'curve' => 'smooth',
                    'width' => 3,
                ],
                'dataLabels' => [
                    'enabled' => false,
                ],
                'legend' => [
                    'show' => true,
                    'position' => 'top',
                ],
                'grid' => [
                    'borderColor' => '#f3f4f6',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'shared' => true,
                ],
            ];
        } catch (\Exception $e) {
            return $this->getEmptyChart();
        }
    }

    /**
     * Get empty chart configuration
     */
    protected function getEmptyChart(): array
    {
        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
            ],
            'series' => [
                [
                    'name' => __('app.no_data'),
                    'data' => [0],
                ],
            ],
            'xaxis' => [
                'categories' => [__('app.no_data')],
            ],
        ];
    }
}
