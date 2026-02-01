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
                    ->label('اسم المحافظة')
                    ->required(),
                TextInput::make('follow_up_hours')
                    ->label('ساعات المتابعة')
                    ->required()
                    ->numeric()
                    ->default(0),
               Select::make('shipper_id')
                    ->label('الكابتن الافتراضي')
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
