<?php

namespace App\Filament\Resources\ReturnedShipper\Pages;

use App\Filament\Resources\ReturnedShipperResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnedShippers extends ListRecords
{
    protected static string $resource = ReturnedShipperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
