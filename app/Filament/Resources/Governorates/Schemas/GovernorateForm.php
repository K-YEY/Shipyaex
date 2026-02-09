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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:Governorate'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNameField:Governorate'))
                    ->required(),
                TextInput::make('follow_up_hours')
                    ->label(__('app.follow_up_hours'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewFollowUpHoursColumn:Governorate'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditFollowUpHoursField:Governorate'))
                    ->required()
                    ->numeric()
                    ->default(0),
               Select::make('shipper_id')
                    ->label(__('app.default_shipper'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:Governorate'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditShipperField:Governorate'))
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
