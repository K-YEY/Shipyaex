<?php

namespace App\Filament\Resources\Shippingcontents\Pages;

use App\Filament\Resources\Shippingcontents\ShippingcontentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingcontents extends ListRecords
{
    protected static string $resource = ShippingcontentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
