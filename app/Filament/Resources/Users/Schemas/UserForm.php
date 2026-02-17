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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNameColumn:User'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNameField:User'))
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('phone')
                    ->label(__('app.phone'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPhoneColumn:User'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditPhoneField:User'))
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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewAddressColumn:User'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditAddressField:User'))
                    ->required()
                    ->maxLength(500),

                TextInput::make('username')
                    ->label(__('app.username'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewUsernameField:User'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditUsernameField:User'))
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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPasswordField:User'))
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditPasswordField:User'))
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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCommissionField:User'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0)
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditCommission:User')),
                    
                Select::make('shipping_content_id')
                    ->label(__('app.shipping_contents'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShippingContentField:User'))
                    ->relationship('shippingContents', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                    
                Select::make('plan_id')
                    ->label(__('app.plan'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewPlanField:User'))
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditPlan:User')),
                    
                Select::make('roles')
                    ->label(__('filament-shield::filament-shield.resource.label.roles'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewRolesField:User'))
                    ->relationship('roles', 'name', fn ($query) => $query->where('name', '!=', 'super_admin'))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditRoles:User')),
                    
                Toggle::make('is_blocked')
                    ->label(__('app.is_blocked'))
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('BlockUser:User'))
                    ->default(false)
                    ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('BlockUser:User')),
            ]);
    }
}
