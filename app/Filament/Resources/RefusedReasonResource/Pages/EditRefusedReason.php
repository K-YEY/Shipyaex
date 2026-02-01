<?php

namespace App\Filament\Resources\RefusedReasonResource\Pages;

use App\Filament\Resources\RefusedReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRefusedReason extends EditRecord
{
    protected static string $resource = RefusedReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
