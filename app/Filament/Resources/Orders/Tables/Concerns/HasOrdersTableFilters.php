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

            

            Filter::make('is_return')
                ->label(__('orders.has_return'))
                ->toggle()
                ->query(fn (Builder $query) => $query->where('has_return', true))
                ->visible($isAdmin || self::userCan('ViewHasReturnFilter:Order')),
        ];
    }
}
