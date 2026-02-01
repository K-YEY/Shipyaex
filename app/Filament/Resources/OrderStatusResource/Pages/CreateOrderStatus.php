<?php

namespace App\Filament\Resources\OrderStatusResource\Pages;

use App\Filament\Resources\OrderStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderStatus extends CreateRecord
{
    protected static string $resource = OrderStatusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
