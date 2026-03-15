<?php

namespace App\Filament\Resources\Orders\Tables\Concerns;

use App\Models\Order;
use App\Models\Setting;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Carbon\Carbon;

trait InteractsWithOrdersTable
{
    protected static ?bool $cachedUserIsAdmin = null;
    protected static array $permissionCache = [];
    protected static $currentUser = null;
    protected static ?bool $cachedCanEditLocked = null;

    // ⚡ Get Sum of a column from the CURRENTLY DISPLAYED records only
    public static function getHeaderSum(Table $table, string $column)
    {
        $query = $table->getFilteredQuery();
        return DB::table('order')->whereIn('id', $query->select('id'))->sum($column);
    }

    public static function requireShipperFirst(): bool
    {
        static $cached = null;
        if ($cached === null) $cached = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
        return $cached;
    }

    public static function getFollowUpHours(): int
    {
        static $cached = null;
        if ($cached === null) $cached = (int) Setting::get('follow_up_hours', 24);
        return $cached;
    }

    public static function userCan(string $permission): bool
    {
        if (!auth()->check()) return false;
        $key = auth()->id() . ':' . $permission;
        if (!isset(self::$permissionCache[$key])) self::$permissionCache[$key] = auth()->user()->can($permission);
        return self::$permissionCache[$key];
    }

    public static function isFieldDisabled($record): bool
    {
        if (!$record) return true;
        if (self::$cachedUserIsAdmin) return false;
        if (self::isRecordLocked($record)) return true;
        if (!self::userCan('EditLocked:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) return true;
        return false;
    }

    public static function isRecordLocked($record): bool
    {
        if (!$record) return false;
        if (self::$cachedUserIsAdmin) return false;
        return $record->collected_shipper || $record->collected_client || $record->has_return || $record->return_shipper || $record->return_client;
    }

    public static function updateTotalAmount($record, $state): void
    {
        $record->total_amount = $state;
        if (method_exists($record, 'recalculateFinancials')) $record->recalculateFinancials();
        $record->save();
    }

    public static function updateFees($record, $state): void
    {
        $record->fees = $state;
        if (method_exists($record, 'recalculateFinancials')) $record->recalculateFinancials();
        $record->save();
    }

    public static function updateShipperFees($record, $state): void
    {
        $record->shipper_fees = $state;
        if (method_exists($record, 'recalculateFinancials')) $record->recalculateFinancials();
        $record->save();
    }

    public static function updateNetFees($record, $state): void
    {
        $record->total_amount = $state + ($record->fees ?? 0);
        if (method_exists($record, 'recalculateFinancials')) $record->recalculateFinancials();
        $record->save();
    }

    public static function handleBulkCollectShipper($records)
    {
        $count = 0;
        foreach ($records as $record) {
            if (!$record->collected_shipper && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                $record->update(['collected_shipper' => true, 'collected_shipper_date' => now()]);
                $count++;
            }
        }
        Notification::make()->title("Collected {$count} orders from Shipper")->success()->send();
    }

    public static function handleBulkCollectClient($records)
    {
        $count = 0;
        $requireShipperFirst = self::requireShipperFirst();
        foreach ($records as $record) {
            if (!$record->collected_client && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                if ($requireShipperFirst && !$record->collected_shipper) continue;
                $record->update(['collected_client' => true, 'collected_client_date' => now()]);
                $count++;
            }
        }
        Notification::make()->title("Settled {$count} orders for Client")->success()->send();
    }
}
