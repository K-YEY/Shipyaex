<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OrdersStatusChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'ordersStatusChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected function getHeading(): ?string
    {
        return __('app.order_statuses');
    }

    /**
     * Sort order
     */
    protected static ?int $sort = 5;

    /**
     * Widget column span
     */
    protected int | string | array $columnSpan = 1;

    /**
     * Check if user can view this widget
     */
    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->can('ViewWidget:OrdersStatusChart');
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
            $user = auth()->user();
            if (!$user) {
                return $this->getEmptyChart();
            }

            // Get orders data
            $query = Order::query();
            
            // Scoping logic based on behavioral permissions
            $isClient = $user->can('ViewOwn:Order');
            $isShipper = $user->can('ViewAssigned:Order');
            $isAdmin = $user->can('ViewAll:Order');

            if ($isAdmin) {
                // See all - no filter
            } elseif ($isClient) {
                $query->where('client_id', $user->id);
            } elseif ($isShipper) {
                $query->where('shipper_id', $user->id);
            } else {
                return $this->getEmptyChart();
            }

            // Get counts for each status
            $outForDelivery = (clone $query)->where('status', 'out for delivery')->count();
            $delivered = (clone $query)->where('status', 'deliverd')->count();
            $undelivered = (clone $query)->where('status', 'undelivered')->count();
            $hold = (clone $query)->where('status', 'hold')->count();

            // Get totals for each status (Not used in series but kept for potential future use)
            $outForDeliveryTotal = (clone $query)->where('status', 'out for delivery')->sum('total_amount');
            $deliveredTotal = (clone $query)->where('status', 'deliverd')->sum('total_amount');
            $undeliveredTotal = (clone $query)->where('status', 'undelivered')->sum('total_amount');
            $holdTotal = (clone $query)->where('status', 'hold')->sum('total_amount');

            return [
                'chart' => [
                    'type' => 'donut',
                    'height' => 350,
                    'toolbar' => [
                        'show' => true,
                        'tools' => [
                            'download' => true,
                        ],
                    ],
                ],
                'series' => [$outForDelivery, $delivered, $hold, $undelivered],
                'labels' => [
                    __('app.out_for_delivery'),
                    __('app.delivered'),
                    __('app.hold'),
                    __('app.undelivered'),
                ],
                'colors' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                'legend' => [
                    'show' => true,
                    'position' => 'bottom',
                    'horizontalAlign' => 'center',
                    'labels' => [
                        'colors' => '#9ca3af',
                        'useSeriesColors' => false,
                    ],
                    'markers' => [
                        'width' => 12,
                        'height' => 12,
                        'radius' => 12,
                    ],
                    'itemMargin' => [
                        'horizontal' => 10,
                        'vertical' => 5,
                    ],
                ],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'size' => '65%',
                            'labels' => [
                                'show' => true,
                                'name' => [
                                    'show' => true,
                                    'fontSize' => '16px',
                                    'fontWeight' => 600,
                                    'color' => '#9ca3af',
                                ],
                                'value' => [
                                    'show' => true,
                                    'fontSize' => '24px',
                                    'fontWeight' => 700,
                                    'color' => '#1f2937',
                                    'formatter' => null,
                                ],
                                'total' => [
                                    'show' => true,
                                    'label' => __('app.total_orders'),
                                    'fontSize' => '16px',
                                    'fontWeight' => 600,
                                    'color' => '#9ca3af',
                                    'formatter' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'formatter' => null,
                    'style' => [
                        'fontSize' => '14px',
                        'fontWeight' => 600,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'y' => [
                        'formatter' => null,
                        'title' => [
                            'formatter' => null,
                        ],
                    ],
                ],
                'states' => [
                    'hover' => [
                        'filter' => [
                            'type' => 'darken',
                            'value' => 0.15,
                        ],
                    ],
                    'active' => [
                        'filter' => [
                            'type' => 'darken',
                            'value' => 0.25,
                        ],
                    ],
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
                'type' => 'donut',
                'height' => 350,
            ],
            'series' => [1],
            'labels' => [__('app.no_data')],
            'colors' => ['#9ca3af'],
        ];
    }
}
