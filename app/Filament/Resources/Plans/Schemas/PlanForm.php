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
                    ->label('اسم الباقة')
                    ->required(),
                TextInput::make('order_count')
                    ->label('عدد الأوردرات المسموح بيها')
                    ->required()
                    ->numeric()
                    ->default(0),
            
            ]);
    }
}
