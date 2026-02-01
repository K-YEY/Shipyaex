<?php

namespace App\Filament\Resources\RefusedReasonResource\Pages;

use App\Filament\Resources\RefusedReasonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRefusedReason extends CreateRecord
{
    protected static string $resource = RefusedReasonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
