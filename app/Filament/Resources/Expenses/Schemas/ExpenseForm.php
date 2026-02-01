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
                    ->label('اسم المصروف'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('ج.م')
                    ->label('المبلغ'),
            ]);
    }
}
