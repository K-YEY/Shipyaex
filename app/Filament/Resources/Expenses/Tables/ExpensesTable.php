<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->visible(fn () => auth()->user()->can('ViewNameColumn:Expense'))
                    ->searchable()
                    ->label(__('app.expense')),
                TextColumn::make('amount')
                    ->visible(fn () => auth()->user()->can('ViewAmountColumn:Expense'))
                    ->state(fn ($record) => number_format($record->amount, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->label(__('app.amount')),
                TextColumn::make('created_at')
                    ->visible(fn () => auth()->user()->can('ViewDatesColumn:Expense'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('app.date')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('Update:Expense')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('DeleteAny:Expense')),
                ]),
            ]);
    }
}
