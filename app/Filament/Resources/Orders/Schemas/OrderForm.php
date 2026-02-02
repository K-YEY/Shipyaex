<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\City;
use App\Models\Order;
use App\Models\PlanPrice;
use App\Models\Setting;
use App\Models\ShippingContent;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        
        $recalculate = function (Get $get, callable $set) {
            $total = (float) ($get('total_amount') ?? 0);
            $fees = (float) ($get('fees') ?? 0);
            $shipper = (float) ($get('shipper_fees') ?? 0);

            $set('cod', Order::calculateCod($total, $fees));
            $set('cop', Order::calculateCop($fees, $shipper));
        };

        return $schema
            ->components([
                Tabs::make('Order Details')
                    ->tabs([
                        Tab::make(__('app.general_info'))
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('orders.code'))
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(function () {
                                                $prefix = Setting::get('order_prefix', 'SHP');
                                                $digits = (int) Setting::get('order_digits', 5);
                                                $lastOrder = Order::latest('id')->first();

                                                if ($lastOrder && $lastOrder->code) {
                                                    $lastNumber = (int) preg_replace('/\D/', '', $lastOrder->code);
                                                    $nextNumber = $lastNumber + 1;
                                                } else {
                                                    $nextNumber = 1;
                                                }

                                                return $prefix.'-'.str_pad($nextNumber, $digits, '0', STR_PAD_LEFT);
                                            }),
                                        
                                        TextInput::make('external_code')
                                            ->label(__('orders.external_code'))
                                            ->visible(fn() => auth()->user()->can('ViewExternalCode:Order'))
                                            ->disabled(fn() => !auth()->user()->can('EditExternalCode:Order'))
                                            ->placeholder(__('orders.external_code_input_placeholder'))
                                            ->helperText(__('orders.external_code_modal_description')),

                                        Select::make('client_id')
                                            ->label(__('orders.client'))
                                            ->options(function () use ($user) {
                                                if (!auth()->user()->can('EditClient:Order')) {
                                                    return [$user->id => $user->name];
                                                }
                                                return User::permission('Access:Client')
                                                    ->pluck('name', 'id');
                                            })
                                            ->default(fn () => !auth()->user()->can('EditClient:Order') ? $user->id : null)
                                            ->disabled(fn () => !auth()->user()->can('EditClient:Order'))
                                            ->dehydrated()
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('shipping_content', null);
                                            }),

                                        Select::make('shipper_id')
                                            ->label(__('orders.shipper_select_label'))
                                            ->relationship(
                                                name: 'shipper',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->permission('Access:Shipper')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->default(null)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $user = User::find($state);
                                                    $commission = $user?->commission ?? 0;
                                                    $set('shipper_fees', $commission);
                                                } else {
                                                    $set('shipper_fees', null);
                                                }
                                            })
                                            ->visible(fn () => auth()->user()->can('AssignShipper:Order')),

                                        Select::make('status')
                                            ->label(__('orders.status'))
                                            ->options([
                                                'out for delivery' => '🚚 ' . __('app.out_for_delivery'),
                                                'deliverd' => '✅ ' . __('app.delivered'),
                                                'hold' => '⏸️ ' . __('app.hold'),
                                                'undelivered' => '❌ ' . __('app.undelivered'),
                                            ])
                                            ->default('out for delivery')
                                            ->disabled(fn () => ! auth()->user()->can('ChangeStatus:Order')),
                                        
                                        Toggle::make('allow_open')
                                            ->label(__('app.allow_open'))
                                            ->default(true)
                                            ->helperText(__('app.allow_open_helper')),
                                    ]),

                                Textarea::make('order_note')
                                    ->label(__('orders.order_notes'))
                                    ->visible(fn() => auth()->user()->can('ViewOrderNotes:Order'))
                                    ->disabled(fn() => !auth()->user()->can('EditOrderNotes:Order'))
                                    ->placeholder(__('orders.order_notes_input_placeholder'))
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->default(null)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('app.customer_info'))
                            ->icon('heroicon-m-user')
                            ->visible(fn() => auth()->user()->can('ViewCustomerDetails:Order'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('orders.recipient_name'))
                                            ->required()
                                            ->disabled(fn() => !auth()->user()->can('EditCustomerDetails:Order'))
                                            ->datalist(Order::query()->distinct()->pluck('name')->filter()->values()->toArray()),
                                        
                                        TextInput::make('phone')
                                            ->label(__('orders.phone'))
                                            ->required()
                                            ->tel()
                                            ->disabled(fn() => !auth()->user()->can('EditCustomerDetails:Order'))
                                            ->datalist(Order::query()->distinct()->pluck('phone')->filter()->values()->toArray()),
                                        
                                        TextInput::make('phone_2')
                                            ->label(__('orders.phone') . ' 2')
                                            ->tel()
                                            ->disabled(fn() => !auth()->user()->can('EditCustomerDetails:Order'))
                                            ->datalist(Order::query()->distinct()->pluck('phone_2')->filter()->values()->toArray()),

                                        Select::make('shipping_content')
                                            ->label(__('app.shipping_content'))
                                            ->options(function (Get $get) {
                                                $clientId = $get('client_id');
                                                if (! $clientId) return [];
                                                return ShippingContent::whereHas('clients', fn($q) => $q->where('client_id', $clientId))->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->reactive()
                                            ->disabled(fn (Get $get) => ! $get('client_id') || !auth()->user()->can('EditCustomerDetails:Order'))
                                            ->required(fn (Get $get) => (bool) $get('client_id')),

                                        Select::make('governorate_id')
                                            ->label(__('orders.governorate'))
                                            ->relationship('governorate', 'name')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Get $get, callable $set, $state) use ($recalculate) {
                                                if (!$state) return;
                                                $currentShipper = $get('shipper_id');
                                                if (!$currentShipper) {
                                                    $governorate = \App\Models\Governorate::find($state);
                                                    if ($governorate && $governorate->shipper_id) {
                                                        $set('shipper_id', $governorate->shipper_id);
                                                        $shipper = User::find($governorate->shipper_id);
                                                        if ($shipper) $set('shipper_fees', $shipper->commission ?? 0);
                                                    }
                                                }
                                                $clientId = $get('client_id');
                                                if (!$clientId) return;
                                                $client = User::find($clientId);
                                                if (! $client || ! $client->plan_id) return;
                                                $planPrice = PlanPrice::where('plan_id', $client->plan_id)->where('location_id', $state)->first();
                                                if (! $planPrice) return;
                                                $fees = $planPrice->price ?? 0;
                                                $set('fees', $fees);
                                                $shipperFees = $get('shipper_fees') ?? 0;
                                                $set('cop', $fees - $shipperFees);
                                                $total = $get('total_amount') ?? 0;
                                                $set('cod', $total - $fees);
                                                $recalculate($get, $set);
                                            })
                                            ->disabled(fn (Get $get) => ! $get('client_id') || !auth()->user()->can('EditCustomerDetails:Order')),

                                        Select::make('city_id')
                                            ->label(__('orders.city'))
                                            ->options(function (Get $get) {
                                                $areaId = $get('governorate_id');
                                                if (! $areaId) return [];
                                                return City::where('governorate_id', $areaId)->pluck('name', 'id');
                                            })
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn (Get $get) => ! $get('governorate_id') || !auth()->user()->can('EditCustomerDetails:Order')),
                                    ]),

                                Textarea::make('address')
                                    ->label(__('orders.address'))
                                    ->required()
                                    ->disabled(fn() => !auth()->user()->can('EditCustomerDetails:Order'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('app.financial_summary'))
                            ->icon('heroicon-m-banknotes')
                            ->visible(fn() => auth()->user()->can('ViewFinancialSummary:Order'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('total_amount')
                                            ->label(__('orders.total_amount'))
                                            ->numeric()
                                            ->prefix(__('statuses.currency'))
                                            ->live(onBlur: true)
                                            ->disabled(fn() => !auth()->user()->can('EditFinancialSummary:Order'))
                                            ->afterStateUpdated(fn (Get $get, callable $set) => $recalculate($get, $set)),

                                        TextInput::make('fees')
                                            ->label(__('orders.shipping_fees'))
                                            ->numeric()
                                            ->prefix(__('statuses.currency'))
                                            ->live(onBlur: true)
                                            ->disabled(fn() => !auth()->user()->can('EditFinancialSummary:Order'))
                                            ->afterStateUpdated(fn (Get $get, callable $set) => $recalculate($get, $set)),

                                        TextInput::make('shipper_fees')
                                            ->label(__('orders.shipper_commission'))
                                            ->numeric()
                                            ->prefix(__('statuses.currency'))
                                            ->live(onBlur: true)
                                            ->visible(fn () => auth()->user()->can('ViewShipperFees:Order'))
                                            ->disabled(fn() => !auth()->user()->can('EditShipperFees:Order'))
                                            ->afterStateUpdated(fn (Get $get, callable $set) => $recalculate($get, $set)),

                                        TextInput::make('cop')
                                            ->label(__('orders.company_share'))
                                            ->numeric()
                                            ->readonly()
                                            ->visible(fn () => auth()->user()->can('ViewCop:Order'))
                                            ->disabled(fn() => !auth()->user()->can('EditCop:Order')),

                                        TextInput::make('cod')
                                            ->label(__('orders.collection_amount'))
                                            ->numeric()
                                            ->readonly(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
