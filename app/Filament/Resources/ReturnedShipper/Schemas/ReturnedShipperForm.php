<?php

namespace App\Filament\Resources\ReturnedShipper\Schemas;

use App\Models\Order;
use App\Models\User;
use App\Services\ReturnedShipperService;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class ReturnedShipperForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isShipper = $user->isShipper();

        return $schema
            ->components([
                Section::make('معلومات المرتجع')
                    ->description('اختر المندوب وتاريخ المرتجع')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            // اختيار Shipper
                            Select::make('shipper_id')
                                ->label('المندوب')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:ReturnedShipper'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditShipperField:ReturnedShipper'))
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
                                ->afterStateUpdated(function (Set $set) {
                                    $set('selected_orders', []);
                                    $set('number_of_orders', 0);
                                }),

                            // تاريخ المرتجع
                            DatePicker::make('return_date')
                                ->label('تاريخ المرتجع')
                                ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewReturnDateColumn:ReturnedShipper'))
                                ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditReturnDateField:ReturnedShipper'))
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('Y-m-d'),

                            // Status
                            Select::make('status')
                                ->label('الحالة')
                                ->visible(fn ($operation) => (auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:ReturnedShipper')) && $operation === 'edit')
                                ->options([
                                    'pending' => 'قيد المراجعة',
                                    'completed' => 'تم الاعتماد ✅',
                                    'cancelled' => 'ملغى ❌',
                                ])
                                ->default('pending')
                                ->required()
                                ->disabled(fn ($record) => (!auth()->user()->isAdmin() && !auth()->user()->can('EditStatusField:ReturnedShipper')) || ($record && $record->status !== 'pending')),
                        ]),

                        Hidden::make('shipper_id')
                            ->default($user->id)
                            ->visible(fn() => $isShipper && !$isAdmin && !auth()->user()->can('ViewShipperColumn:ReturnedShipper')),
                    ]),

                Section::make('الأوردرات المتاحة للمرتجع')
                    ->description('اختر الأوردرات التي تريد تأكيد رجوعها للمخزن')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('available_orders_info')
                            ->label('')
                            ->content(function (Get $get) use ($user, $isShipper) {
                                $shipperId = $get('shipper_id') ?? ($isShipper ? $user->id : null);
                                if (!$shipperId) return new HtmlString('⚠️ اختر المندوب أولاً');
                                
                                $count = Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperReturn()
                                    ->count();
                                return new HtmlString("📦 عدد المرتجعات المتاحة: <strong>{$count}</strong> طلب");
                            }),

                        CheckboxList::make('selected_orders')
                            ->label('الأوردرات')
                            ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSelectedOrdersField:ReturnedShipper'))
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditSelectedOrdersField:ReturnedShipper'))
                            ->options(function (Get $get, $record) use ($user, $isShipper) {
                                $shipperId = $get('shipper_id') ?? ($isShipper ? $user->id : null);
                                if (!$shipperId) return [];

                                $query = Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperReturn();

                                if ($record) {
                                    $query->orWhere('returned_shipper_id', $record->id);
                                }

                                return $query->with('client')->get()
                                    ->mapWithKeys(fn ($order) => [
                                        $order->id => "#{$order->code} | " . ($order->client?->name ?? 'بدون عميل') . " | {$order->name} | {$order->status}"
                                    ]);
                            })
                            ->columns(1)
                            ->bulkToggleable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('number_of_orders', count($state ?? []));
                            })
                            ->default(function (Get $get, $record) use ($user, $isShipper) {
                                if ($record) return $record->orders->pluck('id')->toArray();
                                
                                $shipperId = $get('shipper_id') ?? ($isShipper ? $user->id : null);
                                if (!$shipperId) return [];
                                
                                return Order::query()
                                    ->where('shipper_id', $shipperId)
                                    ->availableForShipperReturn()
                                    ->pluck('id')
                                    ->toArray();
                            }),
                    ]),

                Section::make('ملخص')
                    ->icon('heroicon-o-calculator')
                    ->columnSpanFull()
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewSummaryField:ReturnedShipper'))
                    ->schema([
                        TextInput::make('number_of_orders')
                            ->label('عدد الأوردرات')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->disabled(fn () => !auth()->user()->isAdmin() && !auth()->user()->can('EditNotesField:ReturnedShipper'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
