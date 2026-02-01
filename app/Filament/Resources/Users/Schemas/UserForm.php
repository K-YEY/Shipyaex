<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Plan;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.name'))
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('phone')
                    ->label(__('app.phone'))
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->unique(
                        table: User::class,
                        column: 'phone',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => __('app.unique_phone'),
                    ]),
                    
                TextInput::make('address')
                    ->label(__('app.address'))
                    ->required()
                    ->maxLength(500),

                TextInput::make('username')
                    ->label(__('app.username'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(
                        table: User::class,
                        column: 'username',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => __('app.unique_username'),
                    ]),
                    
                TextInput::make('password')
                    ->label(__('app.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (?object $record) => $record === null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->minLength(6)
                    ->validationMessages([
                        'min' => __('app.min_password'),
                    ]),
                    
                TextInput::make('commission')
                    ->label(__('orders.shipper_commission'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0),
                    
                Select::make('shipping_content_id')
                    ->label(__('app.shipping_contents'))
                    ->relationship('shippingContents', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                    
                Select::make('plan_id')
                    ->label(__('app.plan'))
                    ->options(
                        Plan::pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload(),
                    
                Select::make('roles')
                    ->label(__('app.status'))
                    ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'super_admin'))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
                    
                Toggle::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->default(false),
            ]);
    }
}
