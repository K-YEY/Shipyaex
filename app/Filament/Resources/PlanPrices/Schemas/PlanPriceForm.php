<?php

namespace App\Filament\Resources\PlanPrices\Schemas;

use App\Models\Governorate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlanPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plan_id')
                    ->label('الباقة')
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('location_id')
                    ->label('المحافظة')
                    ->options(function () {
                        return Governorate::pluck('name', 'id')->toArray();
                    })
                    ->required(),
                TextInput::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->default(null),

            ]);
    }
}
