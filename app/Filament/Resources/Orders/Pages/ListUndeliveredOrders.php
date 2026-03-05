<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\UndeliveredOrderResource;
use Filament\Resources\Pages\ListRecords;
use App\Models\Order;
use Filament\Actions\CreateAction;
class ListUndeliveredOrders extends ListRecords
{
    protected static string $resource = UndeliveredOrderResource::class;

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            CreateAction::make()
                ->label('إضافة أوردر جديد')
                ->icon('heroicon-o-plus')
                ->visible(fn() => $user->can('create', Order::class)),
        ];
    }
}
