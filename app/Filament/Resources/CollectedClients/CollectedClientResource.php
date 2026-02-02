<?php

namespace App\Filament\Resources\CollectedClients;

use App\Filament\Resources\CollectedClients\Pages\CreateCollectedClient;
use App\Filament\Resources\CollectedClients\Pages\EditCollectedClient;
use App\Filament\Resources\CollectedClients\Pages\ListCollectedClients;
use App\Filament\Resources\CollectedClients\Pages\ViewCollectedClient;
use App\Filament\Resources\CollectedClients\Schemas\CollectedClientForm;
use App\Filament\Resources\CollectedClients\Tables\CollectedClientsTable;
use App\Models\CollectedClient;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;

class CollectedClientResource extends Resource
{
    protected static ?string $model = CollectedClient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.collected_clients');
    }

    public static function getModelLabel(): string
    {
        return 'تسوية';
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.collected_clients');
    }

    /**
     * إخفاء الـ Navigation للShipper (Shipper No يرى تحصيل Clients)
     */
    public static function canViewNavigation(): bool
    {
        return auth()->user()->can('ViewAny:CollectedClient');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الماليات والمتابعة';
    }

    public static function form(Schema $schema): Schema
    {
        return CollectedClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectedClientsTable::configure($table);
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
            'index' => ListCollectedClients::route('/'),
            'create' => CreateCollectedClient::route('/create'),
            'view' => ViewCollectedClient::route('/{record}'),
            'edit' => EditCollectedClient::route('/{record}/edit'),
        ];
    }

    /**
     * Modify the query based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->can('ViewAll:CollectedClient')) {
            return $query;
        }

        if ($user->can('ViewOwn:CollectedClient')) {
            return $query->where('client_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Badge للعدد في القائمة الجانبية
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user->can('ViewAll:CollectedClient')) {
            return static::getModel()::where('status', 'pending')->count() ?: null;
        }

        if ($user->can('ViewOwn:CollectedClient')) {
            return static::getModel()::where('client_id', $user->id)
                ->where('status', 'pending')
                ->count() ?: null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
