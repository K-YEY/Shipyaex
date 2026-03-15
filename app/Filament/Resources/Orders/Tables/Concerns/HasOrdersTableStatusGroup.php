<?php

namespace App\Filament\Resources\Orders\Tables\Concerns;

use App\Models\Order;
use App\Models\Setting;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

trait HasOrdersTableStatusGroup
{
    public static function getOrderStatusGroup(): ColumnGroup
    {
        $user = auth()->user();
        $isAdmin = self::$cachedUserIsAdmin;
        
        $canViewShipper = (bool) $user?->can('ViewShipperDetails:Order');
        $canViewCustomer = (bool) $user?->can('ViewCustomerDetails:Order');
        $canViewOrder = (bool) $user?->can('View:Order');

        $statusFields = [
            'collected_shipper' => ['label' => 'تحصيل كابتن', 'visible' => $canViewShipper],
            'return_shipper'    => ['label' => 'مرتجع كابتن', 'visible' => $canViewShipper],
            'has_return'        => ['label' => 'فيه مرتجع', 'visible' => $canViewOrder],
            'collected_client'  => ['label' => 'تسوية عميل', 'visible' => $canViewCustomer],
            'return_client'     => ['label' => 'مرتجع عميل', 'visible' => $canViewCustomer],
        ];

        $columns = [];
        foreach ($statusFields as $field => $config) {
            $columns[] = TextColumn::make($field)
                ->label(new HtmlString(
                    view('filament.tables.columns.status-filter-header', [
                        'label' => $config['label'],
                        'field' => $field,
                    ])->render()
                ))
                ->badge()
                ->searchable(
                    isGlobal: false,
                    isIndividual: true,
                    query: function (Builder $query, string $search) use ($field): Builder {
                        if ($search === '1') return $query->where($field, true);
                        if ($search === '0') return $query->where(fn($q) => $q->where($field, false)->orWhereNull($field));
                        return $query;
                    }
                )
                ->toggleable()
                ->visible($config['visible'])
                ->color(fn ($record) => $record?->{$field} ? 'success' : 'danger')
                ->formatStateUsing(fn ($record) => self::formatStatusField($record, $field));
        }

        return ColumnGroup::make('بيانات التسوية', $columns);
    }

    protected static function formatStatusField($record, string $field): string
    {
        if (!$record || !$record->{$field}) return '✗';
        $dateField = "{$field}_date";
        return $record->{$dateField} ? Carbon::parse($record->{$dateField})->format('Y-m-d') : '✓';
    }

    public static function updateFinancials($record, $field, $state): void
    {
        $record->{$field} = $state;
        if (method_exists($record, 'recalculateFinancials')) {
            $record->recalculateFinancials();
        }
        $record->save();
    }
}
