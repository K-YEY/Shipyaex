<?php

namespace App\Filament\Resources\CollectedShippers\Pages;

use App\Filament\Resources\CollectedShippers\CollectedShipperResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectedShippers extends ListRecords
{
    protected static string $resource = CollectedShipperResource::class;

    protected static ?string $title = 'Shipper Collections';

    protected static ?string $breadcrumb = 'List';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Collection')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Can add widgets here later
        ];
    }
}
