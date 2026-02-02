<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.orders');
    }

    public static function getModelLabel(): string
    {
        return __('app.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.orders');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
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
            'index' => ListOrders::route('/'),
        ];
    }

    /**
     * فلترة الأوردرات حسب دور الUser
     * Client: يرى أوردراته فقط
     * Shipper: يرى الأوردرات الموكلة له فقط
     * Admin: يرى كل الأوردرات
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereNotNull('status');
        
        // ⚡ PERFORMANCE OPTIMIZATION: Eager load all relationships to prevent N+1 queries
        // This reduces database queries from 100+ to ~10 for large datasets
        $query->with([
            'client:id,name,phone,email',
            'shipper:id,name,phone,commission',
            'governorate:id,name',
            'city:id,name,governorate_id',
            'collectedShipper:id,status,created_at',
            'collectedClient:id,status,created_at',
            'returnedShipper:id,status,created_at',
        ]);
        
        $user = auth()->user();

        // ViewAll:Order يرى All
        if ($user->can('ViewAll:Order')) {
            return $query;
        }

        // ViewOwn:Order يرى أوردراته فقط (للعملاء)
        if ($user->can('ViewOwn:Order')) {
            return $query->where('client_id', $user->id);
        }

        // ViewAssigned:Order يرى الأوردرات الموكلة له فقط (للمناديب)
        if ($user->can('ViewAssigned:Order')) {
            return $query->where('shipper_id', $user->id);
        }

        // Default: أظهر All
        return $query;
    }

    /**
     * Badge للعدد في القائمة الجانبية
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            // Financials
            'view_shipper_fees',
            'edit_shipper_fees',
            'view_cop',
            'edit_cop',
            'view_net_fees',
            'view_financial_summary', // Total, Fees, COD
            'edit_financial_summary',
            // Customer Info
            'view_customer_details', // Name, Phone, Address
            'edit_customer_details',
            // Shipper Info
            'view_shipper_details', // Shipper Name, Phone
            'assign_shipper',
            // Order Details
            'view_dates',
            'view_external_code',
            'edit_external_code',
            'view_order_notes',
            'edit_order_notes',
            'view_status_notes',
            'edit_locked',
            'edit_client',
            'manage_collections',
            'cancel_collections',
            'view_location',
            'barcode_scanner',
            // Actions
            'change_status',
            'manage_returns',
            'print_labels',
            'export_data',
        ];
    }
}
