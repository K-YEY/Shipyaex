<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:Plan'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNameField:Plan'))
                    ->required(),
                TextInput::make('order_count')
                    ->label(__('orders.orders'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrderCountColumn:Plan'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditOrderCountField:Plan'))
                    ->required()
                    ->numeric()
                    ->default(0),
            
            ]);
    }
}
