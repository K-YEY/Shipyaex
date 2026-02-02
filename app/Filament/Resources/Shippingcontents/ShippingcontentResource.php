<?php

namespace App\Filament\Resources\Shippingcontents;

use App\Filament\Resources\Shippingcontents\Pages\CreateShippingcontent;
use App\Filament\Resources\Shippingcontents\Pages\EditShippingcontent;
use App\Filament\Resources\Shippingcontents\Pages\ListShippingcontents;
use App\Filament\Resources\Shippingcontents\Schemas\ShippingcontentForm;
use App\Filament\Resources\Shippingcontents\Tables\ShippingcontentsTable;
use App\Models\ShippingContent;
use BackedEnum, UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingContentResource extends Resource
{
    protected static ?string $model = ShippingContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    
    protected static ?int $navigationSort = 23;

    public static function getNavigationLabel(): string
    {
        return __('app.shipping_contents');
    }

    public static function getModelLabel(): string
    {
        return __('app.shipping_content');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.shipping_contents');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return ShippingcontentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingcontentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingcontents::route('/'),
            'create' => CreateShippingcontent::route('/create'),
            'edit' => EditShippingcontent::route('/{record}/edit'),
        ];
    }
}
