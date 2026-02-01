<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnedClient\Pages;
use App\Models\ReturnedClient;
use App\Filament\Resources\ReturnedClient\Schemas\ReturnedClientForm;
use App\Filament\Resources\ReturnedClient\Tables\ReturnedClientsTable;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class ReturnedClientResource extends Resource
{
    protected static ?string $model = ReturnedClient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    public static function getNavigationLabel(): string
    {
        return __('app.returned_clients');
    }

    public static function getModelLabel(): string
    {
        return 'مرتجع';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.returned_clients');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الماليات والمتابعة';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(ReturnedClientForm::configure(Schema::make())->getComponents());
    }

    public static function table(Table $table): Table
    {
        return ReturnedClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnedClients::route('/'),
            'create' => Pages\CreateReturnedClient::route('/create'),
            'view' => Pages\ViewReturnedClient::route('/{record}'),
            'edit' => Pages\EditReturnedClient::route('/{record}/edit'),
        ];
    }
}
