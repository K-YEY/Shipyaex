<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('governorate_id')
                    ->label(__('app.governorate'))
                    ->relationship('governorate', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('app.city'))
                    ->required(),
            ]);
    }
}
