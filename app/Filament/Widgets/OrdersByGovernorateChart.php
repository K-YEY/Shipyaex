<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Governorate;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrdersByGovernorateChart extends ChartWidget
{
    protected static ?int $sort = 6;

    public function getHeading(): ?string
    {
        return '📊 ' . __('app.orders_by_governorate');
    }

    public static function canView(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isClient() || auth()->user()->isShipper();
    }

    protected function getData(): array
    {
        $user = auth()->user();
        
        $query = Order::query()
            ->select('governorate_id', DB::raw('count(*) as total'))
            ->whereNotNull('governorate_id')
            ->groupBy('governorate_id');

        // Apply filters based on role
        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isShipper()) {
            $query->where('shipper_id', $user->id);
        }

        $orderData = $query->get()->pluck('total', 'governorate_id')->toArray();
        
        $governorates = Governorate::whereIn('id', array_keys($orderData))
            ->pluck('name', 'id')
            ->toArray();

        $labels = [];
        $data = [];

        foreach ($orderData as $govId => $total) {
            $labels[] = $governorates[$govId] ?? "Unknown ($govId)";
            $data[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => __('app.total_orders'),
                    'data' => $data,
                    'backgroundColor' => [
                        '#36A2EB', '#FF6384', '#4BC0C0', '#FFCE56', '#9966FF', 
                        '#FF9F40', '#8e5ea2', '#3e95cd', '#e8c3b9', '#c45850'
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Makes it a horizontal bar chart for better readability of governorate names
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
