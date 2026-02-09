<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('app.users') . ' ID')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:User'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('app.copied'))
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('primary'),
                TextColumn::make('username')
                    ->label(__('app.username'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewUsernameColumn:User'))
                    ->searchable()->icon('heroicon-o-hashtag')
                    ->copyable()
                    ->copyMessage(__('app.copied'))
                    ->copyMessageDuration(1500)
                    ->iconPosition(IconPosition::Before)->color('warning')->iconColor('warning'),
                TextColumn::make('name')
                    ->label(__('app.name'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:User'))
                    ->searchable(),
                TextColumn::make('roles.name')->color('info')
                    ->label(__('app.status'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewRolesColumn:User'))
                    ->toggleable()->badge(),
            
                ToggleColumn::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('BlockUser:User'))
                    ->onColor('danger')    // Color when status is 1 (Blocked)
                    ->offColor('success')  // Color when status is 0 (Unblocked)
                    ->onIcon('heroicon-s-lock-closed')   // icon for blocking
                    ->offIcon('heroicon-s-lock-open'),
                TextColumn::make('deleted_at')
                    ->label(__('orders.filters.deleted_orders'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('RestoreAny:User'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('app.created_at'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:User'))
                    ->dateTime('d M, Y H:i')
                    ->since()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('orders.filters.deleted_orders')),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'super_admin'))
                    ->label(__('app.status')),
                SelectFilter::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->options([
                        '1' => __('statuses.yes'),
                        '0' => __('statuses.no'),
                    ]),

            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('Update:User')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('DeleteAny:User')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ForceDeleteAny:User')),
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('RestoreAny:User')),
                ]),
            ]);
    }
}
