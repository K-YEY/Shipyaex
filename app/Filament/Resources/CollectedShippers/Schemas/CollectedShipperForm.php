<?php

namespace App\Filament\Resources\CollectedShippers\Schemas;

use App\Models\Order;
use App\Models\User;
use App\Services\CollectedShipperService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TableRepeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class CollectedShipperForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isShipper = $user->isShipper();

        return $schema
            ->components([
                Section::make('معلومات التحصيل')
                    ->description('اختر المندوب وتاريخ التحصيل')
                    ->icon('heroicon-o-banknotes')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('shipper_id')
                                ->label('المندوب')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:CollectedShipper'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditShipperField:CollectedShipper'))
                                ->relationship(
                                    name: 'shipper',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function ($query) use ($user, $isShipper) {
                                        if ($isShipper && !auth()->user()->isAdmin()) {
                                            return $query->where('id', $user->id);
                                        }
                                        return $query->role('shipper');
                                    }
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($isShipper ? $user->id : null)
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // إعادة تعيين البيانات عند تغيير المندوب
                                    $set('selected_orders', []);
                                    $set('total_amount', 0);
                                    $set('shipper_fees', 0);
                                    $set('fees', 0);
                                    $set('net_amount', 0);
                                    $set('number_of_orders', 0);
                                }),

                            // تاريخ التحصيل
                            DatePicker::make('collection_date')
                                ->label('تاريخ التحصيل')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCollectionDateColumn:CollectedShipper'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditCollectionDateField:CollectedShipper'))
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('Y-m-d'),

                            // Status (للEdit فقط)
                            Select::make('status')
                                ->label('الحالة')
                                ->visible(fn ($operation) => (auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:CollectedShipper')) && $operation === 'edit')
                                ->options(\App\Enums\CollectingStatus::class)
                                ->default('pending')
                                ->required()
                                ->disabled(fn ($record) => (!auth()->user()->isAdmin() && !auth()->user()->can('EditStatusField:CollectedShipper')) || ($record && $record->status !== 'pending')),
                        ]),

                        // Hidden للشيبّر إذا كان الUser هو Shipper ولا يرى حقل الاختيار
                        Hidden::make('shipper_id')
                            ->default($user->id)
                            ->visible(fn() => $isShipper && !$isAdmin && !auth()->user()->can('ViewShipperColumn:CollectedShipper')),
                    ]),

                // قسم Orderات - عرض All مع إمكانية اNoستبعاد
                Section::make('الأوردرات المتاحة للتحصيل')
                    ->description('⚡ سيتم إنشاء فاتورة واحدة للمندوب — حدّد الأوردرات المطلوبة')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        // عرض عدد Orderات المتاحة
                        Placeholder::make('available_orders_info')
                            ->label('')
                            ->content(function (Get $get) use ($user, $isShipper) {
                                $shipperId = $get('shipper_id');
                                if (!$shipperId && $isShipper) {
                                    $shipperId = $user->id;
                                }
                                if (!$shipperId) {
                                    return new HtmlString('<div class="text-warning-600 font-medium">⚠️ اختر المندوب أولاً لعرض الأوردرات المتاحة</div>');
                                }
                                $count = Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperCollecting()
                                    ->count();
                                return new HtmlString("<div class='text-success-600 font-medium'>📦 عدد الأوردرات المتاحة للتحصيل: <strong>{$count}</strong> طلب</div>");
                            }),

                        CheckboxList::make('selected_orders')
                            ->label('الأوردرات (قم بإلغاء تحديد الأوردرات التي لا تريد تحصيلها)')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSelectedOrdersField:CollectedShipper'))
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditSelectedOrdersField:CollectedShipper'))
                            ->options(function (Get $get, $record) use ($user, $isAdmin, $isShipper) {
                                $shipperId = $get('shipper_id');

                                if (!$shipperId) {
                                    $shipperId = $isShipper ? $user->id : null;
                                }

                                if (!$shipperId) {
                                    return [];
                                }

                                $query = Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperCollecting();

                                // في حالة الEdit، نضيف Orderات الحالية
                                if ($record) {
                                    $query->orWhere('collected_shipper_id', $record->id);
                                }

                                return $query->get()
                                    ->sortBy(fn($order) => $order->client?->name ?? 'zzz_بدون عميل')
                                    ->mapWithKeys(function ($order) {
                                        $total = $order->status === 'deliverd' ? ($order->total_amount ?? 0) : 0;
                                        $commission = $order->shipper_fees ?? 0;
                                        $net = $total - $commission;
                                        $clientName = $order->client?->name ?? 'بدون عميل';
                                        
                                        $label = "【{$clientName}】 #{$order->code} | " .
                                                "إجمالي: {$total} | " .
                                                "عمولة: {$commission} | " .
                                                "صافي: {$net} | " .
                                                "الحالة: " . ($order->status === 'deliverd' ? '✅ تم التسليم' : '❌ لم يتم التسليم');
                                                
                                        return [$order->id => $label];
                                    });
                            })
                            ->columns(1)
                            ->bulkToggleable()
                            ->live()
                            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                if (empty($state)) return;

                                $service = new CollectedShipperService();
                                $amounts = $service->calculateAmounts($state);

                                $set('total_amount', $amounts['total_amount']);
                                $set('shipper_fees', $amounts['shipper_fees']);
                                $set('fees', $amounts['fees']);
                                $set('net_amount', $amounts['net_amount']);
                                $set('number_of_orders', $amounts['number_of_orders']);
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (empty($state)) {
                                    $set('total_amount', 0);
                                    $set('shipper_fees', 0);
                                    $set('fees', 0);
                                    $set('net_amount', 0);
                                    $set('number_of_orders', 0);
                                    return;
                                }

                                $service = new CollectedShipperService();
                                $amounts = $service->calculateAmounts($state);

                                $set('total_amount', $amounts['total_amount']);
                                $set('shipper_fees', $amounts['shipper_fees']);
                                $set('fees', $amounts['fees']);
                                $set('net_amount', $amounts['net_amount']);
                                $set('number_of_orders', $amounts['number_of_orders']);
                            })
                            ->default(function (Get $get, $record) use ($user, $isShipper) {
                                // في حالة الEdit، نرجع Orderات المحفوظة
                                if ($record) {
                                    return $record->orders->pluck('id')->toArray();
                                }
                                
                                // في حالة الإنشاء، نختار كل Orderات المتاحة افتراضياً
                                $shipperId = $get('shipper_id');
                                if (!$shipperId && $isShipper) {
                                    $shipperId = $user->id;
                                }
                                if (!$shipperId) {
                                    return [];
                                }
                                
                                return Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperCollecting()
                                    ->pluck('id')
                                    ->toArray();
                            })
                            ->helperText('✅ كل الأوردرات محددة افتراضياً — عند الحفظ سيتم إنشاء فاتورة تحصيل واحدة للمندوب'),
                    ]),

                // قسم ملخص المبالغ
                Section::make('ملخص التحصيل')
                    ->description('حساب المبالغ تلقائي')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSummaryField:CollectedShipper'))
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('number_of_orders')
                            ->label('عدد الأوردرات')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountField:CollectedShipper'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('طلب'),



                        TextInput::make('fees')
                            ->label('شحن')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShippingField:CollectedShipper'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م'),

                        TextInput::make('total_amount')
                            ->label('إجمالي المبلغ')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewTotalAmountField:CollectedShipper'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م'),

                        TextInput::make('shipper_fees')
                            ->label('عمولة المندوب')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperFeesField:CollectedShipper'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م'),

                        TextInput::make('net_amount')
                            ->label('الصافي')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNetAmountField:CollectedShipper'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م')
                            ->extraAttributes(['class' => 'font-bold text-success-600']),
                    ]),

                // مNoحظات
                Section::make('ملاحظات')
                    ->columnSpanFull()
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNotesField:CollectedShipper'))
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNotesField:CollectedShipper'))
                            ->placeholder('أي ملاحظات إضافية...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
