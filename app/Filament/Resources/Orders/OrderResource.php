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
            'scanner' => Pages\ScannerMode::route('/scanner'),
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

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // ✅ 1. Admin: Sees ALL orders
        if ($user->isAdmin() || $user->can('ViewAll:Order')) {
            return $query;
        }

        // ✅ 2. Client: Sees only THEIR orders (where they are the owner)
        if ($user->isClient() || $user->can('ViewOwn:Order')) {
            return $query->where('client_id', $user->id);
        }

        // ✅ 3. Shipper: Sees only ASSIGNED orders (where they are the delivery person)
        if ($user->isShipper() || $user->can('ViewAssigned:Order')) {
            return $query->where('shipper_id', $user->id);
        }

        // Default: Fail Closed (User sees nothing if no role/permission matches)
        return $query->whereRaw('1 = 0');
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
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',

            // Column Visibility
            'view_code_column',
            'view_external_code_column',
            'view_registration_date_column',
            'view_shipper_date_column',
            'view_recipient_name_column',
            'view_phone_column',
            'view_address_column',
            'view_governorate_column',
            'view_city_column',
            'view_total_amount_column',
            'view_shipping_fees_column',
            'view_shipper_commission_column',
            'view_net_amount_column',
            'view_company_share_column',
            'view_collection_amount_column',
            'view_status_column',
            'view_status_notes_column',
            'view_order_notes_column',
            'view_shipper_column',
            'view_client_column',
            'view_dates_column',
            
            // Filters
            'view_delayed_follow_up_filter',
            'view_status_filter',
            'view_collected_from_shipper_filter',
            'view_returned_from_shipper_filter',
            'view_has_return_filter',
            'view_settled_with_client_filter',
            'view_returned_to_client_filter',
            
            // Actions
            'export_selected_action',
            'export_external_codes_action',
            'print_labels_action',
            'assign_shipper_action',
            'bulk_change_status_action',
            'manage_shipper_collection_action',
            'manage_client_collection_action',
            'manage_shipper_return_action',
            'manage_client_return_action',
            'view_my_orders_action',
            'barcode_scanner_action',
            'view_timeline_action',
            'print_label_action',
            'change_status_action',
            
            // Form Fields & Sections
            'view_external_code_field',
            'edit_external_code_field',
            'edit_client_field',
            'assign_shipper_field',
            'change_status_field',
            'view_order_notes_field',
            'edit_order_notes_field',
            'view_customer_details_section',
            'edit_customer_details_field',
            'view_financial_summary_section',
            'edit_financial_summary_field',
            'view_shipper_fees_field',
            'edit_shipper_fees_field',
            'view_cop_field',
            'edit_cop_field',
            
            // Legacy / Scoping
            'view_all',
            'view_own',
            'view_assigned',
            'edit_locked',
            'edit_client',
        ];
    }
}
