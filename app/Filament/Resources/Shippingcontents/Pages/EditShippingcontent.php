<?php

namespace App\Filament\Resources\Shippingcontents\Pages;

use App\Filament\Resources\Shippingcontents\ShippingContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShippingcontent extends EditRecord
{
    protected static string $resource = ShippingContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
