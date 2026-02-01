<?php

namespace App\Filament\Resources\ReturnedClient\Pages;

use App\Filament\Resources\ReturnedClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnedClients extends ListRecords
{
    protected static string $resource = ReturnedClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
