<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Exports\OrdersExport;
use App\Exports\ExternalCodesExport;
use App\Exports\OrdersTemplateExport;
use App\Imports\OrdersImport;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\Summarizers\Sum;
use Maatwebsite\Excel\Facades\Excel;

class OrdersTable
{
    // Order Status Constants
    const STATUS_OUT_FOR_DELIVERY = 'out for delivery';
    const STATUS_DELIVERED = 'deliverd';
    const STATUS_UNDELIVERED = 'undelivered';
    const STATUS_HOLD = 'hold';
    
    // Collection Status Constants
    const COLLECTION_STATUS_COMPLETED = 'completed';
    const COLLECTION_STATUS_PENDING = 'pending';
    
    private static array $totals = [];
    
    
    public static function configure(Table $table): Table
    {
        // Dynamic roles are now used instead of static caching

        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('orders.code'))
                    ->color(function ($record) {
                        try {
                            // Check governorate specific hours first, then fallback to global setting
                            $governorateHours = $record->governorate?->follow_up_hours;
                            $limit = ($governorateHours && $governorateHours > 0) 
                                ? (int) $governorateHours 
                                : (int) \App\Models\Setting::get('order_follow_up_hours', 48);

                            if (in_array($record->status, [
                                self::STATUS_OUT_FOR_DELIVERY
                            ])) {
                                if ($record->updated_at && $record->updated_at->diffInHours(now()) > $limit) {
                                    return 'danger';
                                }
                            }
                        } catch (\Exception $e) {}
                        
                        return 'info';
                    })
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->searchable( isIndividual: true,),
                TextColumn::make('external_code')
                    ->label(__('orders.external_code'))
                    ->color('warning')
                    ->badge()
                    ->sortable() ->alignCenter()
                    ->visible(fn() => auth()->user()->can('ViewExternalCode:Order'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true)
                    ->placeholder(__('orders.external_code_placeholder'))
                    ->action(
                        fn ($record) => auth()->user()->can('EditExternalCode:Order') ? 
                        Action::make('editExternalCode')
                            ->modalHeading(__('orders.external_code_modal_heading'))
                            ->modalDescription(__('orders.external_code_modal_description'))
                            ->form([
                                \Filament\Forms\Components\TextInput::make('external_code')
                                    ->label(__('orders.external_code_input_label'))
                                    ->placeholder(__('orders.external_code_input_placeholder'))
                                    ->default(fn ($record) => $record->external_code),
                            ])
                            ->action(function (Order $record, array $data) {
                                $record->update(['external_code' => $data['external_code']]);
                                
                                Notification::make()
                                    ->title(__('orders.external_code_success'))
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                            })
                            ->modalWidth('sm')
                    : null),
                TextColumn::make('created_at')
                    ->label(__('orders.registration_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->alignCenter()
                    ->visible(fn() => auth()->user()->can('ViewDates:Order'))
                    ->toggleable(),
                TextColumn::make('shipper_date')
                    ->label(__('orders.shipper_date'))
                    ->date('Y-m-d')
                    ->toggleable()  
                    ->searchable(isIndividual: true)
                    ->visible(fn() => auth()->user()->can('ViewDates:Order'))
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('orders.recipient_name'))
                    ->searchable(isIndividual: true)
                    ->alignCenter()
                    ->visible(fn() => auth()->user()->can('ViewCustomerDetails:Order'))
                    ->toggleable(),
                TextColumn::make('customer_phones')
                    ->label(__('orders.phone'))
                    ->getStateUsing(
                        fn ($record) => collect([
                            $record->phone,
                            $record->phone_2,
                        ])
                            ->filter()
                            ->map(fn ($phone) => e($phone)) // Escape for each number
                            ->join('<br>')
                    )
                    ->html() // very important
                    ->visible(fn() => auth()->user()->can('ViewCustomerDetails:Order'))
                    ->searchable(
                        isIndividual: true,
                        query: fn ($query, $search) => $query->where('phone', 'like', "%{$search}%")
                            ->orWhere('phone_2', 'like', "%{$search}%")
                    )
                    ->toggleable()->alignCenter(),
                TextColumn::make('address')
                    ->label(__('orders.address'))
                    ->visible(fn() => auth()->user()->can('ViewCustomerDetails:Order'))
                    ->toggleable()
                    ->searchable(isIndividual: true)
                    ->limit(length: 50, end: "\n...")  // put special ending instead of (more)
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->address),
                TextColumn::make('governorate.name')
                    ->numeric()
                    ->searchable(isIndividual: true)
                    ->visible(fn() => auth()->user()->can('ViewLocation:Order'))
                    ->toggleable()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('city.name')
                    ->searchable(isIndividual: true)
                    ->visible(fn() => auth()->user()->can('ViewLocation:Order'))
                    ->toggleable()
                    ->alignCenter()
                    ->sortable(),
                TextInputColumn::make('total_amount')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.total_amount') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('total_amount'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->prefix(__('statuses.currency'))
                    ->sortable()
                    ->toggleable()
                    ->searchable(isIndividual: true)
                    ->visible(fn() => auth()->user()->can('ViewFinancialSummary:Order'))
                    ->afterStateUpdated(fn ($record, $state) => self::updateTotalAmount($record, $state)),

                TextInputColumn::make('fees')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.shipping_fees') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('fees'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->prefix(__('statuses.currency'))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->sortable()
                    ->visible(fn() => auth()->user()->can('ViewFinancialSummary:Order'))
                    ->searchable(isIndividual: true)
                    ->toggleable()
                    ->afterStateUpdated(fn ($record, $state) => self::updateFees($record, $state)),

                TextInputColumn::make('shipper_fees')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.shipper_commission') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('shipper_fees'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->prefix(__('statuses.currency'))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->sortable()
                    ->visible(fn() => auth()->user()->can('ViewShipperFees:Order'))
                    ->toggleable()
                    ->searchable(isIndividual: true)
                    ->afterStateUpdated(fn ($record, $state) => self::updateShipperFees($record, $state)),
                TextInputColumn::make('net_fees')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.net_amount') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('total_amount') - $this->getFilteredTableQuery()->sum('shipper_fees'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->prefix(__('statuses.currency'))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw("total_amount - COALESCE(shipper_fees, 0) $direction"))
                    ->visible(fn() => auth()->user()->can('ViewNetFees:Order'))
                    ->toggleable()
                    ->searchable(query: fn ($query, $search) => $query->whereRaw("total_amount - COALESCE(shipper_fees, 0) LIKE ?", ["%{$search}%"]), isIndividual: true)
                    ->afterStateUpdated(fn ($record, $state) => self::updateNetFees($record, $state)),

                TextColumn::make('cop')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.company_share') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('cop'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->numeric()
                    ->state(fn ($record) => number_format($record->cop, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->visible(fn() => auth()->user()->can('ViewCop:Order'))
                    ->toggleable()
                    ->alignCenter(),

                TextColumn::make('cod')
                    ->label(fn ($livewire) => new \Illuminate\Support\HtmlString(
                        __('orders.collection_amount') . '<br><span style="color:var(--primary-600); font-weight:bold;">' . 
                        number_format((fn() => $this->getFilteredTableQuery()->sum('cod'))->call($livewire), 2) . 
                        '</span>'
                    ))
                    ->numeric()
                    ->state(fn ($record) => number_format($record->cod, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->visible(fn() => auth()->user()->can('ViewFinancialSummary:Order'))
                    ->searchable(isIndividual: true)
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label(new \Illuminate\Support\HtmlString(
                        view('filament.tables.columns.status-select-header', [
                            'label' => __('orders.status'),
                            'field' => 'status',
                            'options' => [
                                self::STATUS_OUT_FOR_DELIVERY => '🚚 ' . __('app.out_for_delivery'),
                                self::STATUS_DELIVERED => '✅ ' . __('app.delivered'),
                                self::STATUS_UNDELIVERED => '❌ ' . __('app.undelivered'),
                                self::STATUS_HOLD => '⏸️ ' . __('app.hold'),
                            ],
                        ])->render()
                    ))
                    ->badge()
                    ->color(fn ($record) => strtolower($record->orderStatus?->color ?? 'gray'))
                    ->sortable()
                    ->searchable()->alignCenter()  
                    ->toggleable()
                    ->extraAttributes(
                        fn ($record) => self::isRecordLocked($record) || 
                            (!auth()->user()->can('ChangeStatus:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED]))
                            ? []
                            : ['class' => 'cursor-pointer text-primary font-semibold']
                    )
                    ->tooltip(function ($record) {
                        if (self::isRecordLocked($record)) {
                            return __('statuses.tooltip_order_locked');
                        }
                        if (!auth()->user()->can('ChangeStatus:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                            return __('statuses.tooltip_order_closed');
                        }
                        return __('statuses.tooltip_change_status');
                    })
                    ->action(
                        Action::make('changeStatus')
                            ->visible(function ($record) {
                                // User must have permission to change status
                                if (!auth()->user()->can('ChangeStatus:Order', $record)) {
                                    return false;
                                }

                                // Cannot edit if record is locked
                                if (self::isRecordLocked($record)) {
                                    return false;
                                }

                                // If not admin-like (has override), cannot edit closed orders
                                if (!auth()->user()->can('EditLocked:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                                    return false;
                                }

                                return true;
                            })
                            ->modalHeading(fn ($record) => __('statuses.change_status_title', ['code' => $record->code]))
                            ->schema([
                                Select::make('status')
                                    ->label(__('statuses.new_status_label'))
                                    ->options(function () {
                                        // Get active order statuses from database
                                        return \App\Models\OrderStatus::active()
                                            ->ordered()
                                            ->pluck('name', 'slug')
                                            ->toArray();
                                    })
                                    ->default(fn ($record) => $record->status)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        // Check if selected status should clear refused reasons
                                        $orderStatus = \App\Models\OrderStatus::where('slug', $state)->first();
                                        if ($orderStatus && $orderStatus->clear_refused_reasons) {
                                            $set('status_note', []);
                                        }
                                    })
                                    ->required(),

                                TagsInput::make('status_note')
                                    ->label(__('statuses.refused_reasons_notes_label'))
                                    ->placeholder(__('statuses.refused_reasons_placeholder'))
                                    ->suggestions(function ($get) {
                                        $selectedStatus = $get('status');
                                        
                                        if (!$selectedStatus) {
                                            return [];
                                        }

                                        // Get order status and its associated refused reasons
                                        $orderStatus = \App\Models\OrderStatus::where('slug', $selectedStatus)
                                            ->with('refusedReasons')
                                            ->first();

                                        if (!$orderStatus) {
                                            return [];
                                        }

                                        // Return associated refused reasons as suggestions
                                        return $orderStatus->refusedReasons()
                                            ->active()
                                            ->ordered()
                                            ->pluck('name')
                                            ->toArray();
                                    })
                                    ->default(fn ($record) => (array) $record->status_note)
                                    ->reorderable()
                                    ->splitKeys(['Enter', ','])
                                    ->helperText(function ($get) {
                                        $selectedStatus = $get('status');
                                        $orderStatus = \App\Models\OrderStatus::where('slug', $selectedStatus)->first();
                                        
                                        if ($orderStatus && $orderStatus->clear_refused_reasons) {
                                            return __('statuses.clear_reasons_warning');
                                        }
                                        
                                        return __('statuses.refused_reasons_helper');
                                    })
                                    ->disabled(function ($get) {
                                        $selectedStatus = $get('status');
                                        $orderStatus = \App\Models\OrderStatus::where('slug', $selectedStatus)->first();
                                        return $orderStatus && $orderStatus->clear_refused_reasons;
                                    })
                                    ->columnSpanFull(),

                                Toggle::make('has_return')
                                    ->label(__('statuses.is_return_label'))
                                    ->default(fn ($record) => $record->has_return)
                                    ->live()
                                    ->visible(function ($get, $record) {
                                        $status = $get('status') ?? $record->status;

                                        return $status === self::STATUS_DELIVERED && auth()->user()->can('ManageReturns:Order');
                                    }),

                                \Filament\Forms\Components\TextInput::make('total_amount')
                                    ->label(__('statuses.total_amount_label'))
                                    ->numeric()
                                    ->prefix(__('statuses.currency'))
                                    ->default(fn ($record) => $record->total_amount)
                                    ->helperText(__('statuses.total_amount_helper'))
                                    ->visible(function ($get, $record) {
                                        $status = $get('status') ?? $record->status;
                                        
                                        return $status === self::STATUS_DELIVERED;
                                    }),

                            ])
                            ->action(function ($record, $data) {
                                // Check if status should clear refused reasons
                                $orderStatus = \App\Models\OrderStatus::where('slug', $data['status'])->first();
                                $statusNote = $data['status_note'] ?? [];
                                
                                // Auto-clear if status has clear_refused_reasons enabled
                                if ($orderStatus && $orderStatus->clear_refused_reasons) {
                                    $statusNote = [];
                                }

                                // Calculate new COD based on total_amount and fees
                                $totalAmount = $data['total_amount'] ?? $record->total_amount;
                                $fees = $record->fees ?? 0;
                                $cod = $totalAmount - $fees;

                                $record->update([
                                    'status' => $data['status'],
                                    'status_note' => $statusNote,
                                    'total_amount' => $totalAmount,
                                    'cod' => $cod,

                                    // if new status is_deliverd = 1, put timestamp
                                    'deliverd_at' => $data['status'] == self::STATUS_DELIVERED
                                        ? now()
                                        : $record->deliverd_at,
                                    'has_return' => ! empty($data['has_return']) ? 1 : 0,
                                    'return_at' => ! empty($data['has_return']) ? now() : $record->return_at,
                                ]);

                                Notification::make()
                                    ->title(__('statuses.success_update_title'))
                                    ->body(__('statuses.success_update_body', ['amount' => $totalAmount, 'cod' => $cod]))
                                    ->success()
                                    ->send();
                            })                   


                    )      ->extraHeaderAttributes(['style' => 'min-width: 200px']),
                TextColumn::make('status_note')
                    ->label(__('orders.status_notes'))
                    ->badge()                    
                    ->alignCenter()
                    ->extraHeaderAttributes(['style' => 'min-width: 200px'])
                    ->searchable(isIndividual: true)
                    ->color(function ($state) {
                        // Available Filament colors
                        $colors = [
                            'primary',
                            'warning',
                            'danger',
                            'info',
                            'gray',
                        ];

                        return $colors[crc32($state) % count($colors)];
                    })
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }

                        // if it's a JSON String convert it to Array
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : [$state];
                        }

                        // make it Array if not Array
                        if (! is_array($state)) {
                            $state = [$state];
                        }

                        return implode(', ', $state); // Filament will automatically convert it to Badges
                    })
                    ->separator(',')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('order_note')
                    ->label(__('orders.order_notes'))
                    ->color('success')
                    ->badge()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true)
                    ->placeholder(__('orders.order_notes_placeholder'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->order_note)
                    ->action(
                        auth()->user()->can('EditOrderNotes:Order') ? 
                        Action::make('editOrderNote')
                            ->modalHeading(__('orders.order_notes_modal_heading'))
                            ->modalDescription(__('orders.external_code_modal_description'))
                            ->form([
                                \Filament\Forms\Components\Textarea::make('order_note')
                                    ->label(__('orders.order_notes_input_label'))
                                    ->placeholder(__('orders.order_notes_input_placeholder'))
                                    ->rows(4)
                                    ->maxLength(500)
                                    ->default(fn ($record) => $record->order_note),
                            ])
                            ->action(function (Order $record, array $data) {
                                $record->update(['order_note' => $data['order_note']]);
                                
                                Notification::make()
                                    ->title(__('orders.order_notes_success'))
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                            })
                            ->modalWidth('md')
                            : null
                    ),

                TextColumn::make('shipper.name')
                    ->label('الكابتن')
                    ->visible(fn() => auth()->user()->can('ViewShipperDetails:Order'))
                    ->placeholder('➕ عين كابتن')
                    ->color('primary')
                    ->weight('bold')
                    ->description(function ($record) {
                        return $record->shipper->phone;
                    })
                    ->searchable(isIndividual: true)
                    ->toggleable()  
                    ->sortable()
                    ->action(
                        auth()->user()->can('AssignShipper:Order') ? 

                        Action::make('assignShipper')
                            ->modalHeading('🚚 تعيين كابتن للأوردر')
                            ->modalWidth('sm')
                            ->form([
                                Select::make('shipper_id')
                                    ->label('اختار الكابتن')
                                    ->options(
                                        User::permission('Access:Shipper')
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                        if ($state) {
                                            $shipper = User::find($state);
                                            $set('shipper_fees', $shipper?->commission ?? 0);
                                        }
                                    }),
                                
                                \Filament\Forms\Components\TextInput::make('shipper_fees')
                                    ->label('Shipper Commission')
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->required(),

                                    \Filament\Forms\Components\DatePicker::make('shipper_date')
                                    ->label('Shipper Date')


                                    ->defaultFocusedDate(function ($record) {
                                        return $record->shipper_date ?? \Carbon\Carbon::now()->format('Y-m-d');
                                    })
                                    ->required(),
                            ])
                            ->fillForm(fn ($record) => [
                                'shipper_id' => $record->shipper_id,
                                'shipper_fees' => $record->shipper_fees,
                                'shipper_date' => $record->shipper_date,
                            ])
                            ->action(function (Order $record, array $data) {
                                $record->update([
                                    'shipper_id' => $data['shipper_id'],
                                    'shipper_fees' => $data['shipper_fees'],
                                    'shipper_date' => $data['shipper_date'],
                                ]);
                                
                                Notification::make()
                                    ->title('✅ الكابتن اتعين بنجاح')
                                    ->body("أوردر رقم #{$record->code}")
                                    ->success()
                                    ->send();
                            })
                            : null
                    ),
                self::getOrderStatusGroup(),
                TextColumn::make('client.name')
                    ->visible(fn() => auth()->user()->can('EditClient:Order'))
                    ->searchable(isIndividual: true)
                    ->numeric()
                     ->alignCenter()
                    ->toggleable(),
          

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make()
                    ->label(__('orders.filters.deleted_orders'))
                    ->placeholder(__('orders.filters.active_orders'))
                    ->trueLabel(__('orders.filters.deleted_only'))
                    ->falseLabel(__('orders.filters.all_with_deleted'))
                    ->visible(fn() => auth()->user()->can('RestoreAny:Order')),

                \Filament\Tables\Filters\SelectFilter::make('follow_up_status')
                    ->label(__('orders.filters.delay_follow_up'))
                    ->options([
                        'delayed' => __('orders.filters.delayed'),
                        'on_time' => __('orders.filters.on_time'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $globalLimit = (int) \App\Models\Setting::get('order_follow_up_hours', 48);
                        
                        // Calculate dynamic limit based on governorate setting or fallback to global
                        // We use a subquery to get the governorate hours
                        // IF(governorate_hours > 0, governorate_hours, global_limit)
                        $hoursSql = "COALESCE(NULLIF((SELECT follow_up_hours FROM governorates WHERE governorates.id = `order`.governorate_id LIMIT 1), 0), {$globalLimit})";

                        if ($data['value'] === 'delayed') {
                            $query->where('status', self::STATUS_OUT_FOR_DELIVERY)
                                  ->whereRaw("updated_at < DATE_SUB(NOW(), INTERVAL {$hoursSql} HOUR)");
                        }
                        
                        if ($data['value'] === 'on_time') {
                            $query->where('status', self::STATUS_OUT_FOR_DELIVERY)
                                  ->whereRaw("updated_at >= DATE_SUB(NOW(), INTERVAL {$hoursSql} HOUR)");
                        }
                    }),
                    
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label(__('orders.status'))
                    ->options([
                        self::STATUS_OUT_FOR_DELIVERY => '🚚 ' . __('app.out_for_delivery'),
                        self::STATUS_DELIVERED => '✅ ' . __('app.delivered'),
                        self::STATUS_UNDELIVERED => '❌ ' . __('app.undelivered'),
                        self::STATUS_HOLD => '⏸️ ' . __('app.hold'),
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('collected_shipper')
                    ->label(__('orders.filters.collected_from_shipper'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('return_shipper')
                    ->label(__('orders.filters.returned_from_shipper'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('has_return')
                    ->label(__('orders.filters.has_return'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('collected_client')
                    ->label(__('orders.filters.settled_with_client'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('return_client')
                    ->label(__('orders.filters.returned_to_client'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
            ])
            ->recordActions(self::getRecordActions())
            ->headerActions(self::getHeaderActions())
            ->toolbarActions([
                // 📤 EXPORT & PRINT
                BulkActionGroup::make([
                    BulkAction::make('exportSelected')
                        ->label(__('orders.bulk_actions.export_orders'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn() => auth()->user()->can('ExportData:Order'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $orderIds = $records->pluck('id')->toArray();
                            
                            if (empty($orderIds)) {
                                Notification::make()
                                    ->title('No Orders Selected')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            return Excel::download(
                                new OrdersExport(null, null, $orderIds),
                                'orders-selected-' . now()->format('Y-m-d-His') . '.xlsx'
                            );
                        }),
                    
                    BulkAction::make('exportExternalCodes')
                        ->label(__('orders.bulk_actions.export_codes'))
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn() => auth()->user()->can('ExportData:Order'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $orderIds = $records->pluck('id')->toArray();
                            
                            if (empty($orderIds)) {
                                Notification::make()
                                    ->title('No Orders Selected')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            return Excel::download(
                                new ExternalCodesExport($orderIds),
                                'external-codes-' . now()->format('Y-m-d-His') . '.xlsx'
                            );
                        }),
                    
                    BulkAction::make('printLabels')
                        ->label(__('orders.bulk_actions.print_labels'))
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->visible(fn() => auth()->user()->can('PrintLabels:Order'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $orderIds = $records->pluck('id')->toArray();
                            
                            if (empty($orderIds)) {
                                Notification::make()
                                    ->title('No Orders Selected')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            return redirect()->away(route('orders.print-labels', ['ids' => implode(',', $orderIds)]));
                        }),
                ])->label(__('orders.bulk_actions.export_print_group')),
                
                // 📋 ORDER MANAGEMENT
                BulkActionGroup::make([
                    BulkAction::make('assignShipper')
                        ->label(__('orders.bulk_actions.assign_shipper'))
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->visible(fn() => auth()->user()->can('AssignShipper:Order'))
                        ->form([
                            Select::make('shipper_id')
                                ->label(__('orders.shipper_select_label'))
                                ->options(
                                    User::whereHas('roles', fn($q) => $q->where('name', 'shipper'))
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records, array $data) {
                            $shipperId = $data['shipper_id'];
                            $shipper = User::find($shipperId);
                            $shipperFees = $shipper?->commission ?? 0;
                            
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'shipper_id' => $shipperId,
                                    'shipper_fees' => $shipperFees,
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title("✅ Shipper Assigned")
                                ->body("Assigned ({$shipper->name}) to {$count} orders")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('changeStatus')
                        ->label(__('statuses.bulk_change_status_label'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->visible(fn() => auth()->user()->can('ChangeStatus:Order'))
                        ->form([
                            Select::make('status')
                                ->label(__('statuses.bulk_select_status_label'))
                                ->options(function () {
                                    // Get active order statuses from database
                                    return \App\Models\OrderStatus::active()
                                        ->ordered()
                                        ->pluck('name', 'slug')
                                        ->toArray();
                                })
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records, array $data) {
                            $status = $data['status'];
                            
                            // Check if status should clear refused reasons
                            $orderStatus = \App\Models\OrderStatus::where('slug', $status)->first();
                            $shouldClearReasons = $orderStatus && $orderStatus->clear_refused_reasons;
                            
                            $count = 0;
                            foreach ($records as $record) {
                                $updateData = ['status' => $status];
                                
                                // Clear status_note if needed
                                if ($shouldClearReasons) {
                                    $updateData['status_note'] = [];
                                }
                                
                                $record->update($updateData);
                                $count++;
                            }

                            $message = __('statuses.bulk_status_changed_success', ['count' => $count, 'status' => $orderStatus->name]);
                            if ($shouldClearReasons) {
                                $message .= ' ' . __('statuses.bulk_reasons_cleared');
                            }

                            Notification::make()
                                ->title(__('statuses.bulk_success_title'))
                                ->body($message)
                                ->success()
                                ->send();
                        }),
                ])->label('📋 Manage Orders'),

                // 💰 SHIPPER COLLECTIONS
                BulkActionGroup::make([
                    BulkAction::make('collectShipper')
                        ->label('Collect from Shipper')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(fn() => auth()->user()->can('ManageCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Collect from Shipper')
                        ->modalDescription('Are you sure you want to collect amounts from Shipper for selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $user = auth()->user();
                            $shipperId = null;
                            $count = 0;
                            $totalAmount = 0;
                            $shipperFees = 0;
                            $orderIds = [];
                            $skipped = 0;

                            foreach ($records as $record) {
                                // Check if order is in valid status for collection
                                if ($record->status != self::STATUS_DELIVERED && $record->status != self::STATUS_UNDELIVERED) {
                                    $skipped++;
                                    continue;
                                }

                                // Determine shipper from first order
                                if (!$shipperId && $record->shipper_id) {
                                    $shipperId = $record->shipper_id;
                                }
                                
                                $count++;
                                $totalAmount += $record->total_amount ?? 0;
                                $shipperFees += $record->shipper_fees ?? 0;
                                $orderIds[] = $record->id;
                            }

                            if ($count > 0 && $shipperId) {
                                // Check if there's an existing pending collection for this shipper
                                $existingCollection = \App\Models\CollectedShipper::where('shipper_id', $shipperId)
                                    ->where('status', 'pending')
                                    ->first();

                                if ($existingCollection) {
                                    // Add to existing pending collection
                                    $existingCollection->update([
                                        'total_amount' => $existingCollection->total_amount + $totalAmount,
                                        'shipper_fees' => $existingCollection->shipper_fees + $shipperFees,
                                        'net_amount' => ($existingCollection->total_amount + $totalAmount) - ($existingCollection->shipper_fees + $shipperFees),
                                        'number_of_orders' => $existingCollection->number_of_orders + $count,
                                        'notes' => ($existingCollection->notes ?? '') . "\nAdded {$count} orders on " . now()->format('Y-m-d H:i'),
                                    ]);

                                    $collection = $existingCollection;
                                } else {
                                    // Create new collection record with pending status
                                    $collection = \App\Models\CollectedShipper::create([
                                        'shipper_id' => $shipperId,
                                        'collection_date' => now(),
                                        'total_amount' => $totalAmount,
                                        'shipper_fees' => $shipperFees,
                                        'net_amount' => $totalAmount - $shipperFees,
                                        'number_of_orders' => $count,
                                        'status' => 'pending',
                                        'notes' => 'Created from orders table - awaiting approval',
                                    ]);
                                }

                                // Mark orders as collected and link to collection
                                \App\Models\Order::whereIn('id', $orderIds)
                                    ->update([
                                        'collected_shipper' => true,
                                        'collected_shipper_date' => now(),
                                        'collected_shipper_id' => $collection->id
                                    ]);

                                // Notify success
                                Notification::make()
                                    ->title("✅ Collection Created")
                                    ->body("Created collection with {$count} orders - Status: Pending")
                                    ->success()
                                    ->send();
                            } else {
                                $message = "No valid orders to collect";
                                if ($skipped > 0) {
                                    $message .= "\n⚠️ {$skipped} orders skipped (invalid status or already collected)";
                                }
                                
                                Notification::make()
                                    ->title("⚠️ Cannot Proceed")
                                    ->body($message)
                                    ->warning()
                                    ->send();
                            }
                        }),
                    
                    BulkAction::make('uncollectShipper')
                        ->label('Cancel Collection')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(fn() => auth()->user()->can('CancelCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Shipper Collection')
                        ->modalDescription('Are you sure you want to cancel collection for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->collected_shipper) {
                                    $record->update([
                                        'collected_shipper' => false,
                                        'collected_shipper_date' => null,
                                        'collected_shipper_id' => null,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("✅ Collection Cancelled")
                                ->body("Cancelled collection of {$count} orders from Shipper")
                                ->success()
                                ->send();
                        }),
                ])->label('💰 Shipper Collection'),
                
                // 💵 CLIENT COLLECTIONS
                BulkActionGroup::make([
                    BulkAction::make('collectClient')
                        ->label('Collect for Client')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('info')
                        ->visible(fn() => auth()->user()->can('ManageCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Collect for Client')
                        ->modalDescription('Are you sure you want to collect amounts for the client for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $requireShipperFirst = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
                            
                            $clientId = null;
                            $count = 0;
                            $skipped = 0;
                            $totalCod = 0;
                            $orderIds = [];
                            
                            foreach ($records as $record) {
                                // Check if order is in valid status for collection
                                if ($record->status != self::STATUS_DELIVERED && $record->status != self::STATUS_UNDELIVERED) {
                                    $skipped++;
                                    continue;
                                }

                                // Check if shipper collection is required first
                                if ($requireShipperFirst && !$record->collected_shipper) {
                                    $skipped++;
                                    continue;
                                }
                                
                                // Determine client from first order
                                if (!$clientId && $record->client_id) {
                                    $clientId = $record->client_id;
                                }
                                
                                $count++;
                                $totalCod += $record->cod ?? 0;
                                $orderIds[] = $record->id;
                            }

                            if ($count > 0 && $clientId) {
                                // Check if there's an existing pending collection for this client
                                $existingCollection = \App\Models\CollectedClient::where('client_id', $clientId)
                                    ->where('status', 'pending')
                                    ->first();

                                if ($existingCollection) {
                                    // Add to existing pending collection
                                    $existingCollection->update([
                                        'total_amount' => $existingCollection->total_amount + $totalCod,
                                        'number_of_orders' => $existingCollection->number_of_orders + $count,
                                        'notes' => ($existingCollection->notes ?? '') . "\nAdded {$count} orders on " . now()->format('Y-m-d H:i'),
                                    ]);

                                    $collection = $existingCollection;
                                } else {
                                    // Create new collection record with pending status
                                    $collection = \App\Models\CollectedClient::create([
                                        'client_id' => $clientId,
                                        'collection_date' => now(),
                                        'total_amount' => $totalCod,
                                        'number_of_orders' => $count,
                                        'status' => 'pending',
                                        'notes' => 'Created from orders table - awaiting approval',
                                    ]);
                                }

                                // Mark orders as collected and link to collection
                                \App\Models\Order::whereIn('id', $orderIds)
                                    ->update([
                                        'collected_client' => true,
                                        'collected_client_date' => now(),
                                        'collected_client_id' => $collection->id
                                    ]);

                                // Notify success
                                Notification::make()
                                    ->title("✅ Collection Created")
                                    ->body("Created collection with {$count} orders - Status: Pending")
                                    ->success()
                                    ->send();
                            } else {
                                $message = "No valid orders to collect";
                                if ($skipped > 0) {
                                    $message .= "\n⚠️ {$skipped} orders skipped (Shipper not collected)";
                                }
                                
                                Notification::make()
                                    ->title("⚠️ Cannot Proceed")
                                    ->body($message)
                                    ->warning()
                                    ->send();
                            }
                        }),
                    
                    BulkAction::make('uncollectClient')
                        ->label('Cancel Collection')
                        ->icon('heroicon-o-x-circle')
                        ->color('info')
                        ->visible(fn() => auth()->user()->can('CancelCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Client Collection')
                        ->modalDescription('Are you sure you want to cancel collection for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->collected_client) {
                                    $record->update([
                                        'collected_client' => false,
                                        'collected_client_date' => null,
                                        'collected_client_id' => null,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("✅ Collection Cancelled")
                                ->body("Cancelled collection of {$count} orders for Client")
                                ->success()
                                ->send();
                        }),
                ])->label('💵 Client Collection'),
                
                // ↩️ RETURNS
                BulkActionGroup::make([
                    BulkAction::make('returnShipper')
                        ->label('Shipper Return')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn() => auth()->user()->can('ManageReturns:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Create Shipper Return')
                        ->modalDescription('Are you sure you want to create a return for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $orderIds = [];
                            $shipperId = null;
                            $count = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                // Check if order is in valid status for return
                                if ($record->status != self::STATUS_DELIVERED && $record->status != self::STATUS_UNDELIVERED) {
                                    $skipped++;
                                    continue;
                                }

                                // Only include orders that are not already in a return
                                if (!$record->returned_shipper_id) {
                                    $orderIds[] = $record->id;
                                    $count++;
                                    
                                    // Get shipper from first valid order
                                    if (!$shipperId && $record->shipper_id) {
                                        $shipperId = $record->shipper_id;
                                    }
                                } else {
                                    $skipped++;
                                }
                            }

                            if (empty($orderIds)) {
                                Notification::make()
                                    ->title("⚠️ No Valid Orders")
                                    ->body("All selected orders are already in a return")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if there's an existing pending return for this shipper
                            $existingReturn = \App\Models\ReturnedShipper::where('shipper_id', $shipperId)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingReturn) {
                                // Add to existing pending return
                                $existingReturn->update([
                                    'number_of_orders' => $existingReturn->number_of_orders + $count,
                                    'notes' => ($existingReturn->notes ?? '') . "\nAdded {$count} orders on " . now()->format('Y-m-d H:i'),
                                ]);

                                $return = $existingReturn;
                            } else {
                                // Create new return record with pending status
                                $return = \App\Models\ReturnedShipper::create([
                                    'shipper_id' => $shipperId,
                                    'return_date' => now(),
                                    'number_of_orders' => $count,
                                    'status' => 'pending',
                                    'notes' => 'Created from orders table - awaiting approval',
                                ]);
                            }

                            // Mark orders and link to return
                            \App\Models\Order::whereIn('id', $orderIds)
                                ->update([
                                    'returned_shipper_id' => $return->id,
                                    'return_shipper' => true,
                                    'return_shipper_date' => now(),
                                ]);

                            // Notify success
                            Notification::make()
                                ->title("✅ Return Created")
                                ->body("Created return with {$count} orders - Status: Pending")
                                ->success()
                                ->send();
                        }),
                    
                    BulkAction::make('returnClient')
                        ->label('Client Return')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn() => auth()->user()->can('ManageReturns:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Create Client Return')
                        ->modalDescription('Are you sure you want to create a return for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $orderIds = [];
                            $clientId = null;
                            $count = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                // Check if order is in valid status for return
                                if ($record->status != self::STATUS_DELIVERED && $record->status != self::STATUS_UNDELIVERED) {
                                    $skipped++;
                                    continue;
                                }

                                // Only include orders that have shipper return and no client return yet
                                if (!$record->return_shipper) {
                                    $skipped++;
                                    continue;
                                }

                                if (!$record->returned_client_id) {
                                    $orderIds[] = $record->id;
                                    $count++;
                                    
                                    // Get client from first valid order
                                    if (!$clientId && $record->client_id) {
                                        $clientId = $record->client_id;
                                    }
                                } else {
                                    $skipped++;
                                }
                            }

                            if (empty($orderIds)) {
                                $message = "No valid orders for client return";
                                if ($skipped > 0) {
                                    $message .= "\n⚠️ {$skipped} orders don't have shipper return activated";
                                }
                                
                                Notification::make()
                                    ->title("⚠️ No Valid Orders")
                                    ->body($message)
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if there's an existing pending return for this client
                            $existingReturn = \App\Models\ReturnedClient::where('client_id', $clientId)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingReturn) {
                                // Add to existing pending return
                                $existingReturn->update([
                                    'number_of_orders' => $existingReturn->number_of_orders + $count,
                                    'notes' => ($existingReturn->notes ?? '') . "\nAdded {$count} orders on " . now()->format('Y-m-d H:i'),
                                ]);

                                $return = $existingReturn;
                            } else {
                                // Create new return record with pending status
                                $return = \App\Models\ReturnedClient::create([
                                    'client_id' => $clientId,
                                    'return_date' => now(),
                                    'number_of_orders' => $count,
                                    'status' => 'pending',
                                    'notes' => 'Created from orders table - awaiting approval',
                                ]);
                            }

                            // Mark orders and link to return
                            \App\Models\Order::whereIn('id', $orderIds)
                                ->update([
                                    'returned_client_id' => $return->id,
                                    'return_client' => true,
                                    'return_client_date' => now(),
                                ]);

                            // Notify success
                            Notification::make()
                                ->title("✅ Return Created")
                                ->body("Created return with {$count} orders - Status: Pending")
                                ->success()
                                ->send();
                        }),
                ])->label('↩️ Returns'),
                
                // 🗑️ DELETE
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete')
                        ->visible(fn() => auth()->user()->can('DeleteAny:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Delete Orders')
                        ->modalDescription('Are you sure you want to delete selected orders? You can restore them later.')
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-uturn-up')
                        ->color('danger')
                        ->visible(fn() => auth()->user()->can('RestoreAny:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Restore Orders')
                        ->modalDescription('Are you sure you want to restore deleted orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->trashed()) {
                                    $record->restore();
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("✅ Orders Restored")
                                ->body("Restored {$count} orders successfully")
                                ->success()
                                ->send();
                        }),
                    
                    BulkAction::make('forceDelete')
                        ->label('Force Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn() => auth()->user()->can('ForceDeleteAny:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('⚠️ Force Delete')
                        ->modalDescription('This action cannot be undone! Orders will be permanently deleted from the database.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->forceDelete();
                                $count++;
                            }
                            
                            Notification::make()
                                ->title("⚠️ Permanently Deleted")
                                ->body("Permanently deleted {$count} orders")
                                ->danger()
                                ->send();
                        }),
                ])->label('🗑️ Delete'),
            ])->recordAction(null)->striped()
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormMaxHeight('400px')
            ->defaultPaginationPageOption(500)
            ->paginationPageOptions([10, 25, 50, 100, 500, 1000])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->filtersFormColumns(3)
            ->poll('20s')
            ->defaultSort('created_at', 'desc')
            ->extraAttributes([
                'id' => 'orders-table-wrapper',
            ])
            ->description(new \Illuminate\Support\HtmlString('
                <style>
                    #orders-table-wrapper .fi-ta-ctn {
                        max-height: calc(100vh - 190px);
                        overflow: auto !important;
                        position: relative;
                        border: 1px solid rgb(var(--gray-200));
                        border-radius: 0.5rem;
                    }
                    .dark #orders-table-wrapper .fi-ta-ctn {
                        border-color: rgb(var(--gray-700));
                    }
                    /* Fix for Toggle Columns Dropdown to prevent screen overflow */
                    .fi-dropdown-panel {
                        max-height: 45vh !important;
                        overflow-y: auto !important;
                    }
                </style>
            '));
    }

    private static function getHeaderActions(): array
    {
        return [
            // Display shipper orders
            Action::make('myOrders')
                ->label('My Orders')
                ->color('info')
                ->visible(fn() => auth()->user()->hasRole('shipper') || auth()->user()->can('ViewAny:Order'))
                ->modalHeading('My Orders - Out for Delivery')
                ->modalWidth('7xl')
                ->modalContent(function () {
                    $user = auth()->user();
                    $orders = Order::where('shipper_id', $user->id)
                        ->where('status', self::STATUS_OUT_FOR_DELIVERY)
                        ->with(['governorate', 'city'])
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    return view('filament.orders.shipper-orders-table', [
                        'orders' => $orders,
                        'shipper' => $user,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            
            // Barcode Scanner Action
            Action::make('barcodeScanner')
                ->label('Barcode Scanner')
                ->color('warning')
                ->modalHeading('Quick Barcode Scanner')
                ->modalDescription('Scan barcode or type Order Code to search and control quickly')
                ->modalWidth('2xl')
                ->visible(fn() => auth()->user()->can('BarcodeScanner:Order'))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('scanned_code')
                        ->label('Order Code')
                        ->placeholder('Scan barcode or type code...')
                        ->autofocus()
                        ->extraInputAttributes([
                            'class' => 'text-xl font-mono text-center font-bold',
                            'autocomplete' => 'off',
                            'style' => 'letter-spacing: 2px;',
                        ])
                        ->required()
                        ->live(debounce: 200)
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                            if ($state && strlen($state) >= 2) {
                                // Optimized search with Eager Loading
                                $order = Order::with(['client', 'shipper', 'governorate', 'city'])
                                    ->where('code', $state)
                                    ->orWhere('code', 'like', "%{$state}%")
                                    ->first();
                                
                                if ($order) {
                                    $set('order_id', $order->id);
                                    $set('order_data', [
                                        'code' => $order->code,
                                        'name' => $order->name,
                                        'phone' => $order->phone,
                                        'status' => $order->status,
                                        'total_amount' => $order->total_amount,
                                        'shipper_name' => $order->shipper?->name ?? '-',
                                        'client_name' => $order->client?->name ?? '-',
                                        'collected_shipper' => $order->collected_shipper,
                                        'collected_client' => $order->collected_client,
                                        'has_return' => $order->has_return,
                                        'return_shipper' => $order->return_shipper ?? false,
                                        'return_client' => $order->return_client ?? false,
                                    ]);
                                } else {
                                    $set('order_id', null);
                                    $set('order_data', null);
                                }
                            }
                        }),
                    
                    \Filament\Forms\Components\Placeholder::make('order_info')
                        ->label('Order Info')
                        ->content(function ($get) {
                            $orderData = $get('order_data');
                            if (!$orderData) {
                                return '🔍 Waiting for scan...';
                            }
                            
                            return view('filament.components.order-quick-info', ['order' => $orderData]);
                        })
                        ->columnSpanFull(),
                    
                    \Filament\Forms\Components\Hidden::make('order_id'),
                    \Filament\Forms\Components\Hidden::make('order_data'),
                    
                    \Filament\Forms\Components\Select::make('action_type')
                        ->label('Action Required')
                        ->options(function ($get) {
                            $orderData = $get('order_data');
                            $options = [];
                            
                            if (!$orderData) {
                                return $options;
                            }
                            
                            // View Order
                            $options['view'] = 'View Order Details';
                            
                            // Change Status
                            if (auth()->user()->can('ChangeStatus:Order')) {
                                if ($orderData['status'] !== self::STATUS_DELIVERED) {
                                    $options['mark_delivered'] = 'Delivered';
                                }
                                if ($orderData['status'] !== self::STATUS_UNDELIVERED) {
                                    $options['mark_undelivered'] = 'Mark Undelivered';
                                }
                                if ($orderData['status'] !== self::STATUS_HOLD) {
                                    $options['mark_hold'] = 'Hold Order';
                                }
                                if ($orderData['status'] !== self::STATUS_OUT_FOR_DELIVERY) {
                                    $options['mark_out_for_delivery'] = 'Out for Delivery';
                                }
                            }
                                                        // Collect from Shipper
                                if (auth()->user()->can('ManageCollections:Order')) {
                                    if ($orderData['collected_shipper']) {
                                        $options['uncollect_shipper'] = 'Cancel Shipper Collection';
                                    } else {
                                        $options['collect_shipper'] = 'Collect from Shipper';
                                    }
                                }                                                      // Collect for Client
                                if (auth()->user()->can('ManageCollections:Order')) {
                                    if ($orderData['collected_client']) {
                                        $options['uncollect_client'] = 'Cancel Client Collection';
                                    } else {
                                        $options['collect_client'] = 'Collect for Client';
                                    }
                                }                                                      // Returns
                                if (auth()->user()->can('ManageReturns:Order')) {
                                    if ($orderData['has_return']) {
                                        $options['cancel_return_shipper'] = 'Cancel Shipper Return';
                                    } else {
                                        $options['mark_return_shipper'] = 'Activate Shipper Return';
                                    }
                                }                                                      // Client Return
                                if (auth()->user()->can('ManageReturns:Order')) {
                                    $options['toggle_return_client'] = $orderData['return_client'] ?? false ? 'Cancel Client Return' : 'Activate Client Return';
                                }                                                      // Print Receipt
                                if (auth()->user()->can('PrintLabels:Order')) {
                                    $options['print_label'] = 'Print Shipping Label';
                                }                                                      // Timeline
                                if (auth()->user()->can('ViewStatusNotes:Order')) {
                                    $options['view_timeline'] = 'View Timeline';
                                }                          
                            return $options;
                        })
                        ->default('view')
                        ->required()
                        ->visible(fn ($get) => $get('order_id') !== null)
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $orderId = $data['order_id'] ?? null;
                    $actionType = $data['action_type'] ?? 'view';
                    
                    if (!$orderId) {
                        Notification::make()
                            ->title('Order Not Found')
                            ->body("Code: {$data['scanned_code']}")
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $order = Order::find($orderId);
                    
                    if (!$order) {
                        Notification::make()
                            ->title('Error')
                            ->body('Order not found')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    switch ($actionType) {
                        case 'mark_delivered':
                            $order->update([
                                'status' => self::STATUS_DELIVERED,
                                'deliverd_at' => now(),
                            ]);
                            Notification::make()
                                ->title("Order #{$order->code} Delivered")
                                ->success()
                                ->send();
                            break;
                            
                        case 'mark_undelivered':
                            $order->update(['status' => self::STATUS_UNDELIVERED]);
                            Notification::make()
                                ->title("Order #{$order->code} Marked as Undelivered")
                                ->warning()
                                ->send();
                            break;
                            
                        case 'mark_hold':
                            $order->update(['status' => self::STATUS_HOLD]);
                            Notification::make()
                                ->title("Order #{$order->code} Held")
                                ->info()
                                ->send();
                            break;
                            
                        case 'mark_out_for_delivery':
                            $order->update(['status' => self::STATUS_OUT_FOR_DELIVERY]);
                            Notification::make()
                                ->title("Order #{$order->code} Status Updated to Out for Delivery")
                                ->success()
                                ->send();
                            break;
                            
                        case 'collect_shipper':
                            $order->update([
                                'collected_shipper' => true,
                                'collected_shipper_date' => now(),
                            ]);
                            Notification::make()
                                ->title("Order #{$order->code} Collected from Shipper")
                                ->success()
                                ->send();
                            break;
                            
                        case 'uncollect_shipper':
                            $order->update([
                                'collected_shipper' => false,
                                'collected_shipper_date' => null,
                            ]);
                            Notification::make()
                                ->title("Shipper Collection Cancelled for Order #{$order->code}")
                                ->warning()
                                ->send();
                            break;
                            
                        case 'collect_client':
                            $requireShipperFirst = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
                            
                            if ($requireShipperFirst && !$order->collected_shipper) {
                                Notification::make()
                                    ->title('Cannot Collect for Client')
                                    ->body('Shipper collection must be completed first')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $order->update([
                                'collected_client' => true,
                                'collected_client_date' => now(),
                            ]);
                            Notification::make()
                                ->title("Order #{$order->code} Collected for Client")
                                ->success()
                                ->send();
                            break;
                            
                        case 'uncollect_client':
                            $order->update([
                                'collected_client' => false,
                                'collected_client_date' => null,
                            ]);
                            Notification::make()
                                ->title("Client Collection Cancelled for Order #{$order->code}")
                                ->warning()
                                ->send();
                            break;
                            
                        case 'mark_return_shipper':
                            $order->update([
                                'has_return' => true,
                                'return_shipper' => true,
                                'return_shipper_date' => now(),
                            ]);
                            Notification::make()
                                ->title("Shipper Return Activated")
                                ->body("Order #{$order->code}")
                                ->success()
                                ->send();
                            break;
                            
                        case 'cancel_return_shipper':
                            $order->update([
                                'has_return' => false,
                                'return_shipper' => false,
                                'return_shipper_date' => null,
                            ]);
                            Notification::make()
                                ->title("Shipper Return Cancelled")
                                ->body("Order #{$order->code}")
                                ->warning()
                                ->send();
                            break;
                            
                        case 'toggle_return_client':
                            $newStatus = !($order->return_client ?? false);
                            $order->update([
                                'return_client' => $newStatus,
                                'return_client_date' => $newStatus ? now() : null,
                            ]);
                            Notification::make()
                                ->title($newStatus ? "Client Return Activated" : "Client Return Cancelled")
                                ->body("Order #{$order->code}")
                                ->success()
                                ->send();
                            break;
                            
                        case 'print_label':
                            Notification::make()
                                ->title("Opening Shipping Label")
                                ->body("Order #{$order->code}")
                                ->info()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('open')
                                        ->label('Open Label')
                                        ->url(route('orders.print-label', $order->id))
                                        ->openUrlInNewTab(),
                                ])
                                ->persistent()
                                ->send();
                            break;
                            
                        case 'view_timeline':
                            $histories = $order->statusHistories()->with('user')->latest()->take(10)->get();
                            $timelineHtml = '';
                            foreach ($histories as $history) {
                                $timelineHtml .= "• {$history->created_at->format('Y-m-d H:i')} - {$history->status} by {$history->user?->name}\n";
                            }
                            
                            Notification::make()
                                ->title("Timeline - Order #{$order->code}")
                                ->body($timelineHtml ?: 'No records found')
                                ->info()
                                ->persistent()
                                ->send();
                            break;
                            
                        default:
                            Notification::make()
                                ->title("Order Info #{$order->code}")
                                ->body("
                                    Name: {$order->name}
                                    Phone: {$order->phone}
                                    Status: {$order->status}
                                    Amount: {$order->total_amount} EGP
                                ")
                                ->info()
                                ->persistent()
                                ->send();
                    }
                })
                ->modalSubmitActionLabel('Execute')
                ->modalCancelActionLabel('Close')
                ->keyBindings(['ctrl+q', 'cmd+q']),

            // Export Action - for Admin only
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn() => auth()->user()->can('ExportData:Order'))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('limit')
                        ->label('Number of Orders')
                        ->placeholder('Leave empty to Export All')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leave empty to Export all orders'),
                ])
                ->action(function (array $data) {
                    $limit = !empty($data['limit']) ? (int) $data['limit'] : null;
                    return Excel::download(
                        new OrdersExport(null, $limit), 
                        'orders-' . now()->format('Y-m-d-His') . '.xlsx'
                    );
                }),

            // Import Action - for Admin only
            Action::make('import')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn() => auth()->user()->can('Create:Order'))
                ->schema([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),

                    Select::make('client_id')
                        ->label('Client (Optional)')
                        ->options(
                            User::whereHas('roles', fn($q) => $q->where('name', 'client'))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->placeholder('Select Client or leave empty'),

                    Select::make('shipper_id')
                        ->label('Shipper (Optional)')
                        ->options(
                            User::whereHas('roles', fn($q) => $q->where('name', 'shipper'))
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->placeholder('Select Shipper or leave empty'),
                ])
                ->action(function (array $data) {
                    // Get filename
                    $file = $data['file'];
                    $fileName = is_array($file) ? reset($file) : $file;
                    
                    // Use Storage facade to get path
                    $disk = \Illuminate\Support\Facades\Storage::disk('local');
                    
                    // List of possible paths to search
                    $possiblePaths = [
                        $fileName,
                        'imports/' . $fileName,
                        'imports/' . basename($fileName),
                        'livewire-tmp/' . $fileName,
                        'livewire-tmp/' . basename($fileName),
                    ];
                    
                    $filePath = null;
                    foreach ($possiblePaths as $path) {
                        if ($disk->exists($path)) {
                            $filePath = $disk->path($path);
                            break;
                        }
                    }
                    
                    // If file not found
                    if (!$filePath || !file_exists($filePath)) {
                        Notification::make()
                            ->title('❌ File Upload Error')
                            ->body('File not found. Please try uploading again.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $import = new OrdersImport(
                        $data['client_id'] ?? null,
                        $data['shipper_id'] ?? null
                    );
                    
                    try {
                        Excel::import($import, $filePath);

                        // Delete the file after import
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }

                        $results = $import->getResults();

                        if ($results['errors'] > 0) {
                            // Display errors in detail
                            $errorMessage = "Failed: {$results['errors']} orders\n\n";
                            $errorMessage .= "Errors:\n";
                            $errorMessage .= implode("\n", array_slice($results['error_details'], 0, 5));
                            
                            if (count($results['error_details']) > 5) {
                                $errorMessage .= "\n... and " . (count($results['error_details']) - 5) . " more errors";
                            }
                            
                            Notification::make()
                                ->title('Import Completed with Some Errors')
                                ->body($errorMessage)
                                ->warning()
                                ->persistent()
                                ->send();
                                
                            if (!empty($results['error_details'])) {
                                Log::warning('Import Errors: ' . implode(' | ', $results['error_details']));
                            }
                        } else {
                            Notification::make()
                                ->title('Orders Imported Successfully')
                                ->body("Added {$results['success']} new orders")
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        // Delete file in case of error
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                        
                        Notification::make()
                            ->title('File Rejected')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('downloadTemplate')
                ->label('Download Excel Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn() => auth()->user()->can('Create:Order'))
                ->action(function () {
                    return Excel::download(
                        new OrdersTemplateExport(),
                        'orders-template-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }

    private static function getRecordActions(): array
    {
        return [
            ActionGroup::make([
                // WhatsApp Action
                Action::make('whatsapp')
                    ->label('واتساب للعميل')
                    ->icon(new \Illuminate\Support\HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>')) 
                    // Or use standard icon: 'heroicon-o-chat-bubble-left-right'
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn ($record) => "https://wa.me/+20" . ltrim($record->phone, '0'), shouldOpenInNewTab: true)
                    ->visible(fn ($record) => !empty($record->phone)),

                // Shipper cannot edit if order is delivered
                // Client is forbidden from editing completely
                // Admin can edit anything
                EditAction::make()->visible(function($record) {
                    // User must have update permission
                    if (!auth()->user()->can('Update:Order')) {
                        return false;
                    }

                    // Cannot edit if record is locked
                    if (self::isRecordLocked($record)) {
                        return false;
                    }

                    // Check if user has specific permission to edit closed orders
                    if (!auth()->user()->can('EditLocked:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                        return false;
                    }
                    
                    return true;
                }),

                Action::make('timeline')
                    ->visible(fn() => auth()->user()->can('ViewStatusNotes:Order'))
                    ->label('التاريخ والحركة')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading('تاريخ تغييرات الأوردر')
                    ->modalContent(fn($record) => view('filament.orders.timeline', [
                        'histories' => $record->statusHistories()->with('user')->latest()->get(),
                    ])),
           
                Action::make('printLabel')
                    ->label('طباعة البوليصة')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->visible(fn() => auth()->user()->can('PrintLabels:Order'))
                    ->url(fn($record) => route('orders.print-label', $record->id))
                    ->openUrlInNewTab(),
                
                // Collection actions
                ActionGroup::make([
                    Action::make('toggleCollectedShipper')
                        ->label(fn($record) => $record->collected_shipper ? '❌ إلغاء التحصيل من الكابتن' : '✅ تم التحصيل من الكابتن')
                        ->icon('heroicon-o-truck')
                        ->color(fn($record) => $record->collected_shipper ? 'danger' : 'success')
                        ->visible(fn() => auth()->user()->can('ManageCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => $record->collected_shipper ? 'إلغاء تحصيل الكابتن' : 'التحصيل من الكابتن')
                        ->modalDescription(fn($record) => $record->collected_shipper 
                            ? 'متأكد إنك عاوز تلغي التحصيل ده؟' 
                            : "تحصيل مبلغ " . number_format($record->total_amount, 2) . " ج.م")
                        ->action(function ($record) {
                            // If canceling collection
                            if ($record->collected_shipper) {
                                $record->update([
                                    'collected_shipper' => false,
                                    'collected_shipper_date' => null,
                                    'collected_shipper_id' => null,
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Collection Cancelled')
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Validate status
                            if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
                                Notification::make()
                                    ->title('مش ينفع نحصل من الكابتن')
                                    ->body('الأوردر لازم يكون حالته (اتسلم) أو (مجاش/راجع)')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check for existing pending collection
                            $existingCollection = \App\Models\CollectedShipper::where('shipper_id', $record->shipper_id)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingCollection) {
                                // Add to existing pending collection
                                $existingCollection->update([
                                    'total_amount' => $existingCollection->total_amount + ($record->total_amount ?? 0),
                                    'shipper_fees' => $existingCollection->shipper_fees + ($record->shipper_fees ?? 0),
                                    'net_amount' => ($existingCollection->total_amount + ($record->total_amount ?? 0)) - ($existingCollection->shipper_fees + ($record->shipper_fees ?? 0)),
                                    'number_of_orders' => $existingCollection->number_of_orders + 1,
                                    'notes' => ($existingCollection->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $collection = $existingCollection;
                            } else {
                                // Create new collection with pending status
                                $collection = \App\Models\CollectedShipper::create([
                                    'shipper_id' => $record->shipper_id,
                                    'collection_date' => now(),
                                    'total_amount' => $record->total_amount ?? 0,
                                    'shipper_fees' => $record->shipper_fees ?? 0,
                                    'net_amount' => ($record->total_amount ?? 0) - ($record->shipper_fees ?? 0),
                                    'number_of_orders' => 1,
                                    'status' => 'pending',
                                    'notes' => "Created from order #{$record->code} - awaiting approval",
                                ]);
                            }

                            // Mark order as collected
                            $record->update([
                                'collected_shipper' => true,
                                'collected_shipper_date' => now(),
                                'collected_shipper_id' => $collection->id
                            ]);
                            
                            Notification::make()
                                ->title('✅ Collection Created')
                                ->body("Order #{$record->code} - Status: Pending")
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('toggleCollectedClient')
                        ->label(fn($record) => $record->collected_client ? '❌ إلغاء التسوية للعميل' : '💰 تسوية مع العميل')
                        ->icon('heroicon-o-banknotes')
                        ->color(fn($record) => $record->collected_client ? 'danger' : 'primary')
                        ->visible(fn() => auth()->user()->can('ManageCollections:Order'))
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => $record->collected_client ? 'إلغاء تسوية العميل' : 'تسوية للعميل')
                        ->modalDescription(fn($record) => $record->collected_client 
                            ? 'متأكد إنك عاوز تلغي التسوية دي؟' 
                            : "تسوية مبلغ " . number_format($record->cod, 2) . " ج.م للعميل")
                        ->action(function ($record) {
                            // If canceling collection
                            if ($record->collected_client) {
                                $record->update([
                                    'collected_client' => false,
                                    'collected_client_date' => null,
                                    'collected_client_id' => null,
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Collection Cancelled')
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Validate status
                            if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
                                Notification::make()
                                    ->title('مش ينفع نعمل تسوية للعميل')
                                    ->body('الأوردر لازم يكون حالته (اتسلم) أو (مجاش/راجع)')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if shipper collection is required first
                            $requireShipperFirst = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
                            
                            if ($requireShipperFirst && !$record->collected_shipper) {
                                Notification::make()
                                    ->title('Cannot Collect for Client')
                                    ->body('Shipper collection must be completed first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Check for existing pending collection
                            $existingCollection = \App\Models\CollectedClient::where('client_id', $record->client_id)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingCollection) {
                                // Add to existing pending collection
                                $existingCollection->update([
                                    'total_amount' => $existingCollection->total_amount + ($record->cod ?? 0),
                                    'number_of_orders' => $existingCollection->number_of_orders + 1,
                                    'notes' => ($existingCollection->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $collection = $existingCollection;
                            } else {
                                // Create new collection with pending status
                                $collection = \App\Models\CollectedClient::create([
                                    'client_id' => $record->client_id,
                                    'collection_date' => now(),
                                    'total_amount' => $record->cod ?? 0,
                                    'number_of_orders' => 1,
                                    'status' => 'pending',
                                    'notes' => "Created from order #{$record->code} - awaiting approval",
                                ]);
                            }

                            // Mark order as collected
                            $record->update([
                                'collected_client' => true,
                                'collected_client_date' => now(),
                                'collected_client_id' => $collection->id
                            ]);
                            
                            Notification::make()
                                ->title('✅ Collection Created')
                                ->body("Order #{$record->code} - Status: Pending")
                                ->success()
                                ->send();
                        }),
                    
                    // مرتجع Shipper
                    Action::make('toggleReturnShipper')
                        ->label(fn($record) => $record->return_shipper ? '❌ إلغاء مرتجع الكابتن' : '↩️ مرتجع من الكابتن')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color(fn($record) => $record->return_shipper ? 'danger' : 'info')
                        ->visible(fn() => auth()->user()->can('ManageReturns:Order'))
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => $record->return_shipper ? 'إلغاء مرتجع الكابتن' : 'عمل مرتجع من الكابتن')
                        ->modalDescription(fn($record) => $record->return_shipper 
                            ? 'متأكد إنك عاوز تلغي المرتجع ده؟' 
                            : 'تأكيد استلام المرتجع من الكابتن للأوردر ده')
                        ->action(function ($record) {
                            // If canceling return
                            if ($record->return_shipper) {
                                $record->update([
                                    'return_shipper' => false,
                                    'return_shipper_date' => null,
                                    'returned_shipper_id' => null,
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Return Cancelled')
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Validate status
                            if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
                                Notification::make()
                                    ->title('Cannot Create Return')
                                    ->body('Order must be delivered or undelivered')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check for existing pending return
                            $existingReturn = \App\Models\ReturnedShipper::where('shipper_id', $record->shipper_id)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingReturn) {
                                // Add to existing pending return
                                $existingReturn->update([
                                    'number_of_orders' => $existingReturn->number_of_orders + 1,
                                    'notes' => ($existingReturn->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $return = $existingReturn;
                            } else {
                                // Create new return with pending status
                                $return = \App\Models\ReturnedShipper::create([
                                    'shipper_id' => $record->shipper_id,
                                    'return_date' => now(),
                                    'number_of_orders' => 1,
                                    'status' => 'pending',
                                    'notes' => "Created from order #{$record->code} - awaiting approval",
                                ]);
                            }

                            // Mark order as returned
                            $record->update([
                                'returned_shipper_id' => $return->id,
                                'return_shipper' => true,
                                'return_shipper_date' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('✅ Return Created')
                                ->body("Order #{$record->code} - Status: Pending")
                                ->success()
                                ->send();
                        }),
                    
                    Action::make('toggleReturnClient')
                        ->label(fn($record) => $record->return_client ? '❌ Cancel Return' : '↩️ Client Return')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color(fn($record) => $record->return_client ? 'danger' : 'warning')
                        ->visible(fn() => auth()->user()->can('ManageReturns:Order'))
                        ->requiresConfirmation()
                        ->modalHeading(fn($record) => $record->return_client ? 'Cancel Client Return' : 'Create Client Return')
                        ->modalDescription(fn($record) => $record->return_client 
                            ? 'Are you sure you want to cancel this return?' 
                            : 'Create a return for this order')
                        ->action(function ($record) {
                            // If canceling return
                            if ($record->return_client) {
                                $record->update([
                                    'return_client' => false,
                                    'return_client_date' => null,
                                    'returned_client_id' => null,
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Return Cancelled')
                                    ->body("Order #{$record->code}")
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Validate status
                            if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
                                Notification::make()
                                    ->title('مش ينفع نعمل مرتجع للعميل')
                                    ->body('الأوردر لازم يكون حالته (اتسلم) أو (مجاش/راجع)')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if shipper return exists
                            if (!$record->return_shipper) {
                                Notification::make()
                                    ->title('مش ينفع نعمل مرتجع للعميل')
                                    ->body('لازم الكابتن يرجع الأوردر الأول يا ريس')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Check for existing pending return
                            $existingReturn = \App\Models\ReturnedClient::where('client_id', $record->client_id)
                                ->where('status', 'pending')
                                ->first();

                            if ($existingReturn) {
                                // Add to existing pending return
                                $existingReturn->update([
                                    'number_of_orders' => $existingReturn->number_of_orders + 1,
                                    'notes' => ($existingReturn->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $return = $existingReturn;
                            } else {
                                // Create new return with pending status
                                $return = \App\Models\ReturnedClient::create([
                                    'client_id' => $record->client_id,
                                    'return_date' => now(),
                                    'number_of_orders' => 1,
                                    'status' => 'pending',
                                    'notes' => "Created from order #{$record->code} - awaiting approval",
                                ]);
                            }

                            // Mark order as returned
                            $record->update([
                                'returned_client_id' => $return->id,
                                'return_client' => true,
                                'return_client_date' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('✅ Return Created')
                                ->body("Order #{$record->code} - Status: Pending")
                                ->success()
                                ->send();
                        }),
                ])
                ->label('التحصيل')
                ->icon('heroicon-o-arrow-left'),
                
                // ♻️ استرجاع الأوردر الDeleted
                Action::make('restore')
                    ->label('♻️ استرجاع')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn($record) => auth()->user()->can('Restore:Order') && $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('استرجاع الأوردر')
                    ->modalDescription(fn($record) => "هل تريد استرجاع أوردر رقم #{$record->code}؟")
                    ->action(function ($record) {
                        $record->restore();
                        
                        Notification::make()
                            ->title('♻️ تم استرجاع الأوردر')
                            ->body("أوردر رقم #{$record->code}")
                            ->success()
                            ->send();
                    }),
                
                // 🔥 Delete نهائي
                Action::make('forceDelete')
                    ->label('🔥 حذف نهائي')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn($record) => auth()->user()->can('ForceDelete:Order') && $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ حذف نهائي')
                    ->modalDescription(fn($record) => "هذا الإجراء لا يمكن التراجع عنه! سيتم حذف الأوردر رقم #{$record->code} نهائياً.")
                    ->action(function ($record) {
                        $code = $record->code;
                        $record->forceDelete();
                        
                        Notification::make()
                            ->title('🔥 تم الحذف النهائي')
                            ->body("أوردر رقم #{$code}")
                            ->danger()
                            ->send();
                    }),
          ])->visible(fn() => auth()->user()->can('View:Order')),
        ];
    }
    private static function getOrderStatusGroup(): ColumnGroup
    {
        // الأعمدة مع تحديد من يشوفها
        // collected_shipper, return_shipper: الأدمن وShipper فقط (مش الكNoينت)
        // collected_client, return_client: الأدمن والكNoينت فقط (مش Shipper)
        // has_return: الجميع
        $statusFields = [
            'collected_shipper' => ['label' => 'تحصيل كابتن', 'visibleForClient' => false, 'visibleForShipper' => true],
            'return_shipper' => ['label' => 'مرتجع كابتن', 'visibleForClient' => false, 'visibleForShipper' => true],
            'has_return' => ['label' => 'فيه مرتجع', 'visibleForClient' => false, 'visibleForShipper' => true],
            'collected_client' => ['label' => 'تسوية عميل', 'visibleForClient' => true, 'visibleForShipper' => false],
            'return_client' => ['label' => 'مرتجع عميل', 'visibleForClient' => true, 'visibleForShipper' => false],
        ];

        $columns = [];
        foreach ($statusFields as $field => $config) {
            $fieldName = $field;
            $label = $config['label'];
            
            // تحديد الـ visibility حسب الـ Permissions
            $isVisible = false;
            
            if ($fieldName === 'has_return') {
                $isVisible = auth()->user()->can('View:Order');
            } elseif (in_array($fieldName, ['collected_shipper', 'return_shipper'])) {
                $isVisible = auth()->user()->can('ViewShipperDetails:Order');
            } elseif (in_array($fieldName, ['collected_client', 'return_client'])) {
                $isVisible = auth()->user()->can('ViewCustomerDetails:Order');
            }
            
            $columns[] = TextColumn::make($field)
                ->label(new \Illuminate\Support\HtmlString(
                    view('filament.tables.columns.status-filter-header', [
                        'label' => $label,
                        'field' => $fieldName,
                    ])->render()
                ))
                ->badge()
                ->toggleable()
                ->visible($isVisible)
                ->color(fn ($record) => $record->{$field} ? 'success' : 'danger')
                ->formatStateUsing(fn ($record) => self::formatStatusField($record, $field));
        }

        return ColumnGroup::make('بيانات التسوية', $columns);
    }

    private static function toggleStatusField($record, string $field, string $label): void
    {
        if (self::isRecordLocked($record)) {
            Notification::make()
                ->title("🚫 غير مسموح لك بتعديل {$label}")
                ->danger()
                ->send();

            return;
        }

        $newValue = ! $record->{$field};

        // ✅ التحقق من إعداد ترتيب التحصيل عند تفعيل تحصيل Client
        if ($newValue && $field === 'collected_client') {
            $requireShipperFirst = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
            
            if ($requireShipperFirst && !$record->collected_shipper) {
                Notification::make()
                    ->title('❌ لا يمكن تسوية العميل')
                    ->body('يجب التحصيل من الكابتن أولاً قبل تسوية العميل')
                    ->danger()
                    ->send();
                return;
            }
        }

        // لو بنفعّل التحصيل أو بنلغيه
        if ($newValue && in_array($field, ['collected_client', 'collected_shipper'])) {
            // تحديث السجل
            $record->update([
                $field => $newValue,
                "{$field}_date" => $newValue ? now() : null,
            ]);
        } else {
            // لو بنلغي التحصيل
            $record->update([
                $field => $newValue,
                "{$field}_date" => $newValue ? now() : null,
            ]);
        }

        Notification::make()
                ->title($newValue ? "تم تفعيل {$label}" : "تم إلغاء {$label}")
                ->success()
                ->send();
    }

    private static function formatStatusField($record, string $field): string
    {
        if (! $record->{$field}) {
            return '✗';
        }

        $dateField = "{$field}_date";

        return $record->{$dateField}
            ? Carbon::parse($record->{$dateField})->format('Y-m-d')
            : '✓';
    }

    private static function updateTotalAmount($record, $state): void
    {
        $record->total_amount = $state;
        $record->recalculateFinancials();
        $record->save();
    }

    private static function updateFees($record, $state): void
    {
        $record->fees = $state;
        $record->recalculateFinancials();
        $record->save();
    }

    private static function updateShipperFees($record, $state): void
    {
        $record->shipper_fees = $state;
        $record->recalculateFinancials();
        $record->save();
    }

    private static function updateNetFees($record, $state): void
    {
        // Net Fees هو نفسه COD
        // المعادلة: Net Fees = Total Amount - Fees
        // إذا تغير Net Fees، نقوم بتحديث Total Amount
        // New Total = New Net Fees + Fees
        
        $record->total_amount = $state + $record->shipper_fees;
        $record->recalculateFinancials(); // سيقوم بحساب COD وتحديثه ليطابق Net Fees
        $record->save();
    }

    private static function isFieldDisabled($record): bool
    {
        // Disable fields if the user doesn't have update permission or if the record is locked by business rules
        if (!auth()->user()->can('Update:Order')) {
            return true;
        }

        return self::isRecordLocked($record);
    }

    private static function isRecordLocked($record): bool
    {
        // Business logic for locking (if collected, it is locked)
        // Admins/Super Admins might bypass this if they have a specific permission, but for now we follow the existing logic using permission checks
        if (auth()->user()->can('EditLocked:Order')) {
            return false;
        }

        return $record->collected_client_at !== null
            || $record->collected_shipper_at !== null;
    }

    private static function getStatusFilterColumn(): TextColumn
    {
        $statusOptions = [
            self::STATUS_OUT_FOR_DELIVERY => '🚚 Out for Delivery',
            self::STATUS_DELIVERED => '✅ Delivered',
            self::STATUS_UNDELIVERED => '❌ Undelivered',
            self::STATUS_HOLD => '⏸️ Hold',
        ];

        return TextColumn::make('status')
            ->label(new \Illuminate\Support\HtmlString(
                view('filament.tables.columns.status-select-header', [
                    'label' => 'Status',
                    'field' => 'status',
                    'options' => $statusOptions,
                ])->render()
            ))
            ->badge()
            ->color(fn ($record) => strtolower($record->status?->color ?? 'gray'))
            ->sortable()
            ->searchable()
            ->toggleable();
    }
}
