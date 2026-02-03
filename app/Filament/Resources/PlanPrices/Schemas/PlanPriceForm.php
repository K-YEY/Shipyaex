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
                    ->label(__('app.plan'))
                    ->visible(fn () => auth()->user()->can('ViewPlanColumn:PlanPrice'))
                    ->disabled(fn () => !auth()->user()->can('EditPlanField:PlanPrice'))
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('location_id')
                    ->label(__('app.governorate'))
                    ->visible(fn () => auth()->user()->can('ViewLocationColumn:PlanPrice'))
                    ->disabled(fn () => !auth()->user()->can('EditLocationField:PlanPrice'))
                    ->options(function () {
                        return Governorate::pluck('name', 'id')->toArray();
                    })
                    ->required(),
                TextInput::make('price')
                    ->label(__('app.price'))
                    ->visible(fn () => auth()->user()->can('ViewPriceColumn:PlanPrice'))
                    ->disabled(fn () => !auth()->user()->can('EditPriceField:PlanPrice'))
                    ->numeric()
                    ->default(null),

            ]);
    }
}
