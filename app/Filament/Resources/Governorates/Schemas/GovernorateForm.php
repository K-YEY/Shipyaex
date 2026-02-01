<?php

namespace App\Filament\Resources\Governorates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GovernorateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.name'))
                    ->required(),
                TextInput::make('follow_up_hours')
                    ->label(__('app.follow_up_hours'))
                    ->required()
                    ->numeric()
                    ->default(0),
               Select::make('shipper_id')
                    ->label(__('app.default_shipper'))
                    ->relationship(
                        name: 'shipper',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->whereHas('roles', fn($q) => $q->where('name', 'shipper'))
                    )
                    ->searchable()
                    ->preload(),
            ]);
    }
}
