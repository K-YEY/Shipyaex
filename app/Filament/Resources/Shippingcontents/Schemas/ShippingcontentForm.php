<?php

namespace App\Filament\Resources\Shippingcontents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShippingcontentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            TextInput::make("name")
                ->label('اسم نوع الشحنة')
                ->required(),
            ]);
    }
}
