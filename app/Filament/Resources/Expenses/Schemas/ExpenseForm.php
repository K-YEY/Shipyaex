<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('app.name')),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix(__('statuses.currency'))
                    ->label(__('app.amount')),
            ]);
    }
}
