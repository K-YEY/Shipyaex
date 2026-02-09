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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewGovernorateColumn:City'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditGovernorateField:City'))
                    ->relationship('governorate', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('app.city'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:City'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNameField:City'))
                    ->required(),
            ]);
    }
}
