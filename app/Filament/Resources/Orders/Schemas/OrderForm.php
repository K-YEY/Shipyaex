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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isClient = $user?->isClient() ?? false;
        
        // الحسابات الآن تتم في الموديل (Order::boot) تلقائياً عند الSave
        // هنا نعرض القيم المحسوبة للUser فقط (Live Preview)
        $recalculate = function (Get $get, callable $set) {
            $total = (float) ($get('total_amount') ?? 0);
            $fees = (float) ($get('fees') ?? 0);
            $shipper = (float) ($get('shipper_fees') ?? 0);

            // COD - استخدام نفس المعادلة الموجودة في الموديل
            $set('cod', Order::calculateCod($total, $fees));

            // COP - استخدام نفس المعادلة الموجودة في الموديل
            $set('cop', Order::calculateCop($fees, $shipper));
        };

        return $schema
            ->components([
                TextInput::make('code')
                    ->label('كود الأوردر')
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
                
                // 🔗 كود شركة أخرى (اختياري)
                TextInput::make('external_code')
                    ->label('كود برة (اختياري)')
                    ->placeholder('كود من شركة شحن تانية لو متاح')
                    ->helperText('لو الأوردر ده جاي من شركة تانية، ضيف الكود بتاعهم هنا'),

                Select::make('shipper_id')
                    ->label('اختار الكابتن')
                    ->relationship(
                        name: 'shipper',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->permission('access_as_shipper')
                            ->orWhereHas('roles', fn ($q) => $q->where('name', 'shipper'))

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
                    ->hidden($isClient),
                Select::make('client_id')
                    ->label('العميل صاحب الأوردر')
                    ->options(function () use ($isClient, $user) {
                        if ($isClient) {
                            // لو كNoينت، يجيب اسمه بس
                            return [$user->id => $user->name];
                        }

                        // لو مش كNoينت، يجيب كل الكNoينت
                        return User::permission('access_as_client')
                            ->orWhereHas('roles', fn ($q) => $q->where('name', 'client'))
                            ->pluck('name', 'id');
                    })
                    ->default(fn () => $isClient ? $user->id : null)
                    ->disabled($isClient)
                    ->dehydrated()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('shipping_content', null);
                    }),
                TextInput::make('name')
                    ->label('اسم المستلم')
                    ->required()
                    ->datalist(
                        Order::query()
                            ->distinct()
                            ->pluck('name')
                            ->filter()
                            ->values()
                            ->toArray()
                    ),
                TextInput::make('phone')
                    ->label('رقم التليفون')
                    ->required()
                    ->tel()
                    ->datalist(
                        Order::query()
                            ->distinct()
                            ->pluck('phone')
                            ->filter()
                            ->values()
                            ->toArray()
                    ),
                TextInput::make('phone_2')
                    ->label('رقم تليفون تاني (اختياري)')
                    ->tel()
                    ->datalist(
                        Order::query()
                            ->distinct()
                            ->pluck('phone_2')
                            ->filter()
                            ->values()
                            ->toArray()
                    ),

                Select::make('shipping_content')
                    ->label('نوع الشحنة / المحتوى')
                    ->options(function (Get $get) {
                        $clientId = $get('client_id');

                        if (! $clientId) {
                            return [];
                        }

                        return ShippingContent::whereHas('clients', function ($query) use ($clientId) {
                            $query->where('client_id', $clientId);
                        })->pluck('name', 'id');
                    })
                    ->searchable()
                    ->reactive()
                    ->disabled(fn (Get $get) => ! $get('client_id'))
                    ->required(fn (Get $get) => (bool) $get('client_id'))
                    ->afterStateHydrated(function (Set $set, Get $get) {
                        if ($get('shipping_content')) {
                            return;
                        }

                        $clientId = $get('client_id');
                        if (! $clientId) {
                            return;
                        }

                        $first = ShippingContent::whereHas('clients', fn ($q) => $q->where('client_id', $clientId)
                        )->value('id');

                        if ($first) {
                            $set('shipping_content', $first);
                        }
                    }),

                Textarea::make('address')
                    ->label('العنوان بالتفصيل')
                    ->required()
                    ->columnSpanFull(),
                Select::make('governorate_id')
                    ->label('المحافظة')
                    ->relationship('governorate', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, callable $set, $state) use ($recalculate) {
                        if (!$state) {
                            return;
                        }

                        // 🚚 تعيين Shipper التلقائي من Governorate (إذا لم يتم اختيار Shipper)
                        $currentShipper = $get('shipper_id');
                        if (!$currentShipper) {
                            $governorate = \App\Models\Governorate::find($state);
                            if ($governorate && $governorate->shipper_id) {
                                $set('shipper_id', $governorate->shipper_id);
                                
                                // تعيين shipper_fees من Shipper المعين
                                $shipper = User::find($governorate->shipper_id);
                                if ($shipper) {
                                    $set('shipper_fees', $shipper->commission ?? 0);
                                }
                            }
                        }

                        // حساب Fees من PlanPrice
                        $clientId = $get('client_id');
                        if (!$clientId) {
                            return;
                        }

                        $client = User::find($clientId);
                        if (! $client || ! $client->plan_id) {
                            return;
                        }

                        $planPrice = PlanPrice::where('plan_id', $client->plan_id)
                            ->where('location_id', $state)
                            ->first();

                        if (! $planPrice) {
                            return;
                        }

                        $fees = $planPrice->price ?? 0;
                        $set('fees', $fees);

                        $shipperFees = $get('shipper_fees') ?? 0;
                        $set('cop', $fees - $shipperFees);

                        $total = $get('total_amount') ?? 0;
                        $set('cod', $total - $fees);
                        $recalculate($get, $set);

                    })
                    ->disabled(fn (Get $get) => ! $get('client_id'))

                    ->required(fn (Get $get) => (bool) $get('client_id')),

                Select::make('city_id')
                    ->label('المنطقة / المدينة')
                    ->options(function (Get $get) {
                        $areaId = $get('governorate_id');
                        if (! $areaId) {
                            return [];
                        }

                        return City::where('governorate_id', $areaId)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->reactive()
                    ->disabled(fn (Get $get) => ! $get('governorate_id')),
                TextInput::make('total_amount')
                    ->label('إجمالي مبلغ الأوردر')
                    ->numeric()
                    ->prefix('ج.م')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, callable $set) use ($recalculate) {
                        $recalculate($get, $set);
                    }),

                TextInput::make('fees')
                    ->label('مصاريف الشحن')
                    ->numeric()
                    ->prefix('ج.م')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, callable $set) use ($recalculate) {
                        $recalculate($get, $set);
                    }),

                TextInput::make('shipper_fees')
                    ->label('عمولة الكابتن')
                    ->numeric()
                    ->prefix('ج.م')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, callable $set) use ($recalculate) {
                        $recalculate($get, $set);
                    })
                    ->hidden($isClient),
                TextInput::make('cop')
                    ->label('حق الشركة')
                    ->numeric()
                    ->readonly()
                    ->hidden($isClient), // ❌ مخفية للعميل

                TextInput::make('cod')
                    ->label('مبلغ التحصيل (COD)')
                    ->numeric()
                    ->readonly(),
                Select::make('status')
                    ->label('حالة الأوردر')
                    ->options([
                        'out for delivery' => '🚚 خرج للتوصيل',
                        'deliverd' => '✅ اتسلم بسلامة',
                        'hold' => '⏸️ استنى شوية',
                        'undelivered' => '❌ مجاش / راجع',
                    ])
                    ->default('out for delivery'),
                Textarea::make('order_note')
                    ->label('ملاحظات الأوردر')
                    ->placeholder('اكتب أي ملاحظات تهم الكابتن (اختياري)...')
                    ->rows(3)
                    ->maxLength(500)
                    ->default(null)
                    ->columnSpanFull(),
                
                \Filament\Forms\Components\Toggle::make('allow_open')
                    ->label('مسموح بالفتح')
                    ->default(true)
                    ->helperText('هل يسمح للمستلم بفتح الشحنة قبل الدفع؟'),

            ]);
    }
}
