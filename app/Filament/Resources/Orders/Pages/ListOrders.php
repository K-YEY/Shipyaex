<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
    
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

    /**
     * فلترة الأوردرات حسب دور الUser يتم التعامل معها في الResource
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::getResource()::getEloquentQuery();
    }
}
