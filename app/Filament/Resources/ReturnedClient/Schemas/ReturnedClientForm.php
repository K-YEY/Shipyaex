<?php

namespace App\Filament\Resources\ReturnedClient\Schemas;

use App\Models\Order;
use App\Models\User;
use App\Services\ReturnedClientService;
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

class ReturnedClientForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isClient = $user->isClient();

        return $schema
            ->components([
                Section::make('معلومات المرتجع')
                    ->description('اختر العميل وتاريخ المرتجع')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            // Client Selection
                            Select::make('client_id')
                                ->label('العميل')
                                ->visible(fn () => auth()->user()->can('ViewClientColumn:ReturnedClient'))
                                ->disabled(fn () => !auth()->user()->can('EditClientField:ReturnedClient'))
                                ->relationship(
                                    name: 'client',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query) =>
                                        $query->role('client')->where('is_blocked', false)
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($isClient ? $user->id : null)
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('selected_orders', []);
                                    $set('number_of_orders', 0);
                                }),

                            // Return Date
                            DatePicker::make('return_date')
                                ->label('تاريخ الارتجاع')
                                ->visible(fn () => auth()->user()->can('ViewReturnDateColumn:ReturnedClient'))
                                ->disabled(fn () => !auth()->user()->can('EditReturnDateField:ReturnedClient'))
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('Y-m-d'),

                            // Status
                            Select::make('status')
                                ->label('الحالة')
                                ->visible(fn ($operation) => auth()->user()->can('ViewStatusColumn:ReturnedClient') && $operation === 'edit')
                                ->options([
                                    'pending' => 'قيد المراجعة',
                                    'completed' => 'تم الاعتماد ✅',
                                    'cancelled' => 'ملغى ❌',
                                ])
                                ->default('pending')
                                ->required()
                                ->disabled(fn ($record) => !auth()->user()->can('EditStatusField:ReturnedClient') || ($record && $record->status !== 'pending')),
                        ]),

                        Hidden::make('client_id')
                            ->default($user->id)
                            ->visible(fn() => $isClient && !auth()->user()->can('ViewClientColumn:ReturnedClient')),
                    ]),

                Section::make('الأوردرات المتاحة للمرتجع')
                    ->description('اختر الأوردرات التي عادت من المندوب بالفعل')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('available_orders_info')
                            ->label('')
                            ->content(function (Get $get) use ($user, $isClient) {
                                $clientId = $get('client_id') ?? ($isClient ? $user->id : null);
                                if (!$clientId) return new HtmlString('⚠️ اختر العميل أولاً');
                                
                                $count = Order::query()
                                    ->where('client_id', $clientId)
                                    ->where('return_shipper', true)
                                    ->where('return_client', false)
                                    ->whereNull('returned_client_id')
                                    ->count();
                                return new HtmlString("📦 المرتجعات المتاحة: <strong>{$count}</strong> أوردر");
                            }),

                        CheckboxList::make('selected_orders')
                            ->label('الأوردرات')
                            ->visible(fn () => auth()->user()->can('ViewSelectedOrdersField:ReturnedClient'))
                            ->disabled(fn () => !auth()->user()->can('EditSelectedOrdersField:ReturnedClient'))
                            ->options(function (Get $get, $record) use ($user, $isClient) {
                                $clientId = $get('client_id') ?? ($isClient ? $user->id : null);
                                if (!$clientId) return [];

                                $query = Order::query()
                                    ->where('client_id', $clientId)
                                    ->where('return_shipper', true)
                                    ->where('return_client', false)
                                    ->whereNull('returned_client_id');

                                if ($record) {
                                    $query->orWhere('returned_client_id', $record->id);
                                }

                                return $query->get()
                                    ->mapWithKeys(fn ($order) => [
                                        $order->id => "#{$order->code} | {$order->name} | {$order->status}"
                                    ]);
                            })
                            ->columns(1)
                            ->bulkToggleable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('number_of_orders', count($state ?? []));
                            })
                            ->default(function (Get $get, $record) use ($user, $isClient) {
                                if ($record) return $record->orders->pluck('id')->toArray();
                                
                                $clientId = $get('client_id') ?? ($isClient ? $user->id : null);
                                if (!$clientId) return [];
                                
                                return Order::query()
                                    ->where('client_id', $clientId)
                                    ->where('return_shipper', true)
                                    ->where('return_client', false)
                                    ->whereNull('returned_client_id')
                                    ->pluck('id')
                                    ->toArray();
                            }),
                    ]),

                Section::make('الملخص')
                    ->icon('heroicon-o-calculator')
                    ->columnSpanFull()
                    ->visible(fn () => auth()->user()->can('ViewSummaryField:ReturnedClient'))
                    ->schema([
                        TextInput::make('number_of_orders')
                            ->label('عدد الأوردرات')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->disabled(fn () => !auth()->user()->can('EditNotesField:ReturnedClient'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
