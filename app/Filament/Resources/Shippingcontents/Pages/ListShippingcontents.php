<?php

namespace App\Filament\Resources\Shippingcontents\Pages;

use App\Filament\Resources\Shippingcontents\ShippingContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingcontents extends ListRecords
{
    protected static string $resource = ShippingContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
