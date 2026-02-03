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
                    ->visible(fn () => auth()->user()->can('ViewNameColumn:Expense'))
                    ->disabled(fn () => !auth()->user()->can('EditNameField:Expense'))
                    ->required()
                    ->maxLength(255)
                    ->label(__('app.name')),
                TextInput::make('amount')
                    ->visible(fn () => auth()->user()->can('ViewAmountColumn:Expense'))
                    ->disabled(fn () => !auth()->user()->can('EditAmountField:Expense'))
                    ->required()
                    ->numeric()
                    ->prefix(__('statuses.currency'))
                    ->label(__('app.amount')),
            ]);
    }
}
