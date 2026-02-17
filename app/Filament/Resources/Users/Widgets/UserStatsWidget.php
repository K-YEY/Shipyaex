<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class UserStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $isClient = $this->record->isClient();
        $isShipper = $this->record->isShipper();

        $stats = [];

        if ($isClient) {
            $clientOrders = Order::where('client_id', $this->record->id);
            $stats[] = Stat::make('إجمالي الطلبات (عميل)', $clientOrders->count())
                ->description('إجمالي الطلبات المسجلة')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info');

            $stats[] = Stat::make('طلبات تم التسليم', (clone $clientOrders)->where('status', 'deliverd')->count())
                ->description('الطلبات المسلمة بنجاح')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');

            $stats[] = Stat::make('إجمالي المبالغ', number_format((clone $clientOrders)->where('status', 'deliverd')->sum('total_amount'), 2) . ' EGP')
                ->description('المبالغ المحصلة من الأوردرات المسلمة')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary');
        }

        if ($isShipper) {
            $shipperOrders = Order::where('shipper_id', $this->record->id);
            $stats[] = Stat::make('إجمالي الطلبات (مندوب)', $shipperOrders->count())
                ->description('الأوردرات المسندة للمندوب')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning');

            $stats[] = Stat::make('قيد التوصيل', (clone $shipperOrders)->where('status', 'out for delivery')->count())
                ->description('أوردرات لم يتم تسليمها بعد')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info');

            $stats[] = Stat::make('عمولة المندوب', number_format((clone $shipperOrders)->where('status', 'deliverd')->sum('shipper_fees'), 2) . ' EGP')
                ->description('إجمالي العمولات المستحقة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        return $stats;
    }
}
