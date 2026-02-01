<?php

namespace App\Filament\Resources\CollectedClients\Pages;

use App\Filament\Resources\CollectedClients\CollectedClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectedClients extends ListRecords
{
    protected static string $resource = CollectedClientResource::class;

    protected static ?string $title = 'Client Collections';

    protected static ?string $breadcrumb = 'List';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Collection')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
