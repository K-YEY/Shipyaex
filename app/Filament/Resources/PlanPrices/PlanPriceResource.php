<?php

namespace App\Filament\Resources\PlanPrices;

use App\Filament\Resources\PlanPrices\Pages\CreatePlanPrice;
use App\Filament\Resources\PlanPrices\Pages\EditPlanPrice;
use App\Filament\Resources\PlanPrices\Pages\ListPlanPrices;
use App\Filament\Resources\PlanPrices\Schemas\PlanPriceForm;
use App\Filament\Resources\PlanPrices\Tables\PlanPricesTable;
use App\Models\PlanPrice;
use BackedEnum,UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlanPriceResource extends Resource
{
    protected static ?string $model = PlanPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    
    protected static ?int $navigationSort = 22;

    public static function getNavigationLabel(): string
    {
        return __('app.plan_prices');
    }

    public static function getModelLabel(): string
    {
        return 'سعر باقة';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.plan_prices');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات والبيانات';
    }
    public static function form(Schema $schema): Schema
    {
        return PlanPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanPricesTable::configure($table);
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
            'index' => Pages\ManagePlanPrices::route('/'),
            'create' => Pages\CreatePlanPrice::route('/create'),
            'edit' => Pages\EditPlanPrice::route('/{record}/edit'),
        ];
    }
}
