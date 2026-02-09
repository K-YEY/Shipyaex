<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnedShipper\Pages;
use App\Models\ReturnedShipper;
use App\Filament\Resources\ReturnedShipper\Schemas\ReturnedShipperForm;
use App\Filament\Resources\ReturnedShipper\Tables\ReturnedShippersTable;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class ReturnedShipperResource extends Resource
{
    protected static ?string $model = ReturnedShipper::class;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return "مرتجع مندوب #{$record->id} - " . ($record->shipper?->name ?? 'بدون مندوب');
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function getNavigationLabel(): string
    {
        return __('app.returned_shippers');
    }

    public static function getModelLabel(): string
    {
        return 'مرتجع';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.returned_shippers');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الماليات والمتابعة';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(ReturnedShipperForm::configure(Schema::make())->getComponents());
    }

    public static function table(Table $table): Table
    {
        return ReturnedShippersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnedShippers::route('/'),
            'create' => Pages\CreateReturnedShipper::route('/create'),
            'view' => Pages\ViewReturnedShipper::route('/{record}'),
            'edit' => Pages\EditReturnedShipper::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin() || $user->can('ViewAll:ReturnedShipper')) {
            return $query;
        }

        if ($user->can('ViewOwn:ReturnedShipper')) {
            return $query->where('shipper_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
