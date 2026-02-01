<?php

namespace App\Filament\Resources\Governorates;

use App\Filament\Resources\Governorates\Pages\CreateGovernorate;
use App\Filament\Resources\Governorates\Pages\EditGovernorate;
use App\Filament\Resources\Governorates\Pages\ListGovernorates;
use App\Filament\Resources\Governorates\Schemas\GovernorateForm;
use App\Filament\Resources\Governorates\Tables\GovernoratesTable;
use App\Models\Governorate;
use BackedEnum, UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GovernorateResource extends Resource
{
    protected static ?string $model = Governorate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('app.governorates');
    }

    public static function getModelLabel(): string
    {
        return __('app.governorate');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.governorates');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.geographic_locations');
    }

    public static function form(Schema $schema): Schema
    {
        return GovernorateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GovernoratesTable::configure($table);
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
            'index' => ListGovernorates::route('/'),
            'create' => CreateGovernorate::route('/create'),
            'edit' => EditGovernorate::route('/{record}/edit'),
        ];
    }
}
