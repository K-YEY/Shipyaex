<?php

namespace App\Filament\Resources\CollectedClients\Schemas;

use App\Models\Order;
use App\Models\User;
use App\Services\CollectedClientService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CollectedClientForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isClient = $user->isClient();

        return $schema
            ->components([
                Section::make('معلومات التحصيل')
                    ->description('اختر العميل وتاريخ التحصيل')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('client_id')
                                ->label('اسم العميل')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewClientColumn:CollectedClient'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditClientField:CollectedClient'))
                                ->relationship(
                                    name: 'client',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function ($query) use ($user, $isClient) {
                                        if ($isClient && !auth()->user()->isAdmin()) {
                                            return $query->where('id', $user->id);
                                        }
                                        return $query->role('client');
                                    }
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($isClient ? $user->id : null)
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // إعادة تعيين Orderات المستبعدة
                                    $set('selected_orders', []);
                                    $set('total_amount', 0);
                                    $set('fees', 0);
                                    $set('net_amount', 0);
                                    $set('number_of_orders', 0);
                                    $set('delivered_count', 0);
                                    $set('undelivered_count', 0);
                                }),

                            // تاريخ التحصيل
                            DatePicker::make('collection_date')
                                ->label('تاريخ التحصيل')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCollectionDateColumn:CollectedClient'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditCollectionDateField:CollectedClient'))
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('Y-m-d'),

                            // Status (للEdit فقط)
                            Select::make('status')
                                ->label('الحالة')
                                ->visible(fn ($operation) => auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:CollectedClient') && $operation === 'edit')
                                ->options(\App\Enums\CollectingStatus::class)
                                ->default('pending')
                                ->required()
                                ->disabled(fn ($record) => !auth()->user()->isAdmin() || auth()->user()->can('EditStatusField:CollectedClient') || ($record && $record->status !== 'pending')),
                        ]),

                        // Hidden للعميل إذا كان الUser هو Client ولا يرى حقل الاختيار
                        Hidden::make('client_id')
                            ->default($user->id)
                            ->visible(fn() => $isClient && !$isAdmin && !auth()->user()->can('ViewClientColumn:CollectedClient')),
                    ]),

                // قسم Orderات - عرض All مع إمكانية اNoستبعاد
                Section::make('الأوردرات المتاحة للتحصيل')
                    ->description('جميع الأوردرات محددة افتراضياً - قم بإلغاء تحديد الأوردرات التي لا تريد تحصيلها')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        // عرض عدد Orderات المتاحة
                        Placeholder::make('available_orders_info')
                            ->label('')
                            ->content(function (Get $get) use ($user, $isClient) {
                                $clientId = $get('client_id');
                                if (!$clientId && $isClient) {
                                    $clientId = $user->id;
                                }
                                if (!$clientId) {
                                    return new HtmlString('<div class="text-warning-600 font-medium">⚠️ اختر العميل أولاً لعرض الأوردرات المتاحة</div>');
                                }
                                $count = Order::query()
                                    ->where('client_id', $clientId)
                                    ->availableForClientCollecting()
                                    ->count();
                                return new HtmlString("<div class='text-success-600 font-medium'>📦 عدد الأوردرات المتاحة للتحصيل: <strong>{$count}</strong> طلب</div>");
                            }),

                        CheckboxList::make('selected_orders')
                            ->label('الأوردرات (قم بإلغاء تحديد الأوردرات التي لا تريد تحصيلها)')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSelectedOrdersField:CollectedClient'))
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditSelectedOrdersField:CollectedClient'))
                            ->options(function (Get $get, $record) use ($user, $isAdmin, $isClient) {
                                $clientId = $get('client_id');

                                if (!$clientId) {
                                    $clientId = $isClient ? $user->id : null;
                                }

                                if (!$clientId) {
                                    return [];
                                }

                                $query = Order::query()
                                    ->where('client_id', $clientId)
                                    ->availableForClientCollecting();

                                if ($record) {
                                    $query->orWhere('collected_client_id', $record->id);
                                }

                                return $query->get()
                                    ->mapWithKeys(fn ($order) => [
                                        $order->id => "#{$order->code} | {$order->name} | {$order->total_amount} ج.م | مصاريف: {$order->fees} | {$order->status}"
                                    ]);
                            })
                            ->columns(1)
                            ->bulkToggleable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (empty($state)) {
                                    $set('total_amount', 0);
                                    $set('fees', 0);
                                    $set('net_amount', 0);
                                    $set('number_of_orders', 0);
                                    $set('delivered_count', 0);
                                    $set('undelivered_count', 0);
                                    return;
                                }

                                $service = new CollectedClientService();
                                $amounts = $service->calculateAmounts($state);

                                $set('total_amount', $amounts['total_amount']);
                                $set('fees', $amounts['fees']);
                                $set('net_amount', $amounts['net_amount']);
                                $set('number_of_orders', $amounts['number_of_orders']);
                                $set('delivered_count', $amounts['delivered_count']);
                                $set('undelivered_count', $amounts['undelivered_count']);
                            })
                            ->default(function (Get $get, $record) use ($user, $isClient) {
                                // في حالة الEdit، نرجع Orderات المحفوظة
                                if ($record) {
                                    return $record->orders->pluck('id')->toArray();
                                }
                                
                                // في حالة الإنشاء، نختار كل Orderات المتاحة افتراضياً
                                $clientId = $get('client_id');
                                if (!$clientId && $isClient) {
                                    $clientId = $user->id;
                                }
                                if (!$clientId) {
                                    return [];
                                }
                                
                                return Order::query()
                                    ->where('client_id', $clientId)
                                    ->availableForClientCollecting()
                                    ->pluck('id')
                                    ->toArray();
                            })
                            ->helperText('✅ كل الأوردرات محددة افتراضياً - قم بإلغاء تحديد الأوردرات التي لا تريد تحصيلها'),
                    ]),

                // قسم ملخص المبالغ
                Section::make('ملخص التحصيل')
                    ->description('حساب المبالغ تلقائي')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSummaryField:CollectedClient'))
                    ->columns(6)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('number_of_orders')
                            ->label('إجمالي الأوردرات')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountField:CollectedClient'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('طلب'),

                        Placeholder::make('delivered_count_placeholder')
                            ->label('عدد المسلم')
                            ->content(function (Get $get, $record) {
                                if ($record) {
                                    return $record->orders()->where('status', 'deliverd')->count();
                                }
                                return $get('delivered_count') ?? 0;
                            })
                            ->extraAttributes(['class' => 'text-success-600 font-bold']),

                        Placeholder::make('undelivered_count_placeholder')
                            ->label('عدد غير المسلم')
                            ->content(function (Get $get, $record) {
                                if ($record) {
                                    return $record->orders()->where('status', 'undelivered')->count();
                                }
                                return $get('undelivered_count') ?? 0;
                            })
                            ->extraAttributes(['class' => 'text-danger-600 font-bold']),

                        TextInput::make('total_amount')
                            ->label('إجمالي المبلغ')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewTotalAmountField:CollectedClient'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م'),

                        TextInput::make('fees')
                            ->label('مصاريف الشحن')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewFeesField:CollectedClient'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->prefix('ج.م'),

                        TextInput::make('net_amount')
                            ->label('الصافي للعميل')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNetAmountField:CollectedClient'))
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
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNotesField:CollectedClient'))
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNotesField:CollectedClient'))
                            ->placeholder('أي ملاحظات إضافية...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
