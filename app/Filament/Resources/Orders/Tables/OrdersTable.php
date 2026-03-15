<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Tables\Table;
use App\Filament\Resources\Orders\Tables\Concerns\InteractsWithOrdersTable;
use App\Filament\Resources\Orders\Tables\Concerns\HasOrdersTableColumns;
use App\Filament\Resources\Orders\Tables\Concerns\HasOrdersTableFilters;
use App\Filament\Resources\Orders\Tables\Concerns\HasOrdersTableActions;
use App\Filament\Resources\Orders\Tables\Concerns\HasOrdersTableStatusGroup;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    use InteractsWithOrdersTable;
    use HasOrdersTableColumns;
    use HasOrdersTableFilters;
    use HasOrdersTableActions;
    use HasOrdersTableStatusGroup;

    // Order Status Constants
    const STATUS_OUT_FOR_DELIVERY = 'out for delivery';
    const STATUS_DELIVERED = 'deliverd';
    const STATUS_UNDELIVERED = 'undelivered';
    const STATUS_HOLD = 'hold';
    
    // Collection Status Constants
    const COLLECTION_STATUS_COMPLETED = 'completed';
    const COLLECTION_STATUS_PENDING = 'pending';

    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        self::$currentUser = $user;
        self::$cachedUserIsAdmin = $user?->isAdmin() ?? false;

        // ⚡ Pre-cache permissions to avoid per-row overhead
        $permissions = [
            'Update:Order', 'View:Order', 'ChangeStatusAction:Order', 
            'ManageCollections:Order', 'ManageReturns:Order', 'PrintLabelAction:Order'
        ];
        foreach ($permissions as $p) self::userCan($p);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['governorate', 'city', 'shipper', 'client', 'orderStatus'])->latest())
            ->columns(array_merge(
                self::getColumns(),
                [self::getOrderStatusGroup()]
            ))
            ->filters(self::getFilters())
            ->headerActions(self::getHeaderActions())
            ->actions(self::getRecordActions())
            ->bulkActions(self::getBulkActions())
            // ⚡ PERFORMANCE BOOSTER: Load table content via AJAX after page load
            ->deferLoading()
            ->searchable()
            ->searchUsing(function (Builder $query, string $search): void {
                $search = trim($search);
                if ($search === '') return;
                $query->where(function ($q) use ($search) {
                    $q->where('order.phone', '=', $search)
                      ->orWhere('order.code', '=', $search)
                      ->orWhere('order.name', 'like', "%{$search}%");
                });
            })
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->searchOnBlur()
            ->striped()
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::Modal)
            ->persistSearchInSession()
            ->recordAction(null)
            ->persistFiltersInSession();

    }
}
