<?php

namespace App\Filament\Resources\RefusedReasonResource\Pages;

use App\Filament\Resources\RefusedReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRefusedReasons extends ListRecords
{
    protected static string $resource = RefusedReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
