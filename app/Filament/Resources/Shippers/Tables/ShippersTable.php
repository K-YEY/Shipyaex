<?php

namespace App\Filament\Resources\Shippers\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ShippersTable
{
    public static function configure(Table $table): Table
    {
        return $table
    ->query(User::role('shipper'))

            ->columns([
                TextColumn::make('name')
                ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('commission'),

                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
            
            ])
            ->toolbarActions([
       
            ]);
    }
}
