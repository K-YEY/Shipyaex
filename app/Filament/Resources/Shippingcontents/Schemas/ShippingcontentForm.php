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
                ->label(__('app.name'))
                ->required()
                // 1. Field Visibility Permission
                ->visible(fn () => auth()->user()->can('ViewNameColumn:ShippingContent'))
                // 2. Field Mutability Permission
                ->disabled(fn () => !auth()->user()->can('EditNameField:ShippingContent')),
            ]);
    }
}
