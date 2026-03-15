<?php

namespace App\Filament\Resources\Orders\Tables\Concerns;

use App\Models\Order;
use App\Models\User;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

trait HasOrdersTableFilters
{
    public static function getFilters(): array
    {
        $isAdmin = self::$cachedUserIsAdmin;

        return [
            SelectFilter::make('status')
                ->label(__('orders.status'))
                ->options(fn() => \App\Models\OrderStatus::active()->ordered()->pluck('name', 'slug')->toArray())
                ->multiple()
                ->visible($isAdmin || self::userCan('ViewStatusFilter:Order')),

            SelectFilter::make('shipper_id')
                ->label(__('orders.shipper'))
                ->relationship('shipper', 'name', fn ($query) => $query->role('shipper'))
                ->searchable()
                ->multiple()
                ->visible($isAdmin),

            SelectFilter::make('client_id')
                ->label(__('orders.client'))
                ->relationship('client', 'name', fn ($query) => $query->role('client'))
                ->searchable()
                ->multiple()
                ->visible($isAdmin),

            SelectFilter::make('governorate_id')
                ->label(__('orders.governorate'))
                ->relationship('governorate', 'name')
                ->searchable()
                ->multiple(),

            SelectFilter::make('city_id')
                ->label(__('orders.city'))
                ->relationship('city', 'name')
                ->searchable()
                ->multiple(),

            Filter::make('created_at')
                ->form([
                    DatePicker::make('from')->label(__('orders.from_date')),
                    DatePicker::make('until')->label(__('orders.to_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                })->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) $indicators[] = __('orders.from_date') . ': ' . $data['from'];
                    if ($data['until'] ?? null) $indicators[] = __('orders.to_date') . ': ' . $data['until'];
                    return $indicators;
                }),

            Filter::make('is_return')
                ->label(__('orders.has_return'))
                ->toggle()
                ->query(fn (Builder $query) => $query->where('has_return', true))
                ->visible($isAdmin || self::userCan('ViewHasReturnFilter:Order')),
        ];
    }
}
