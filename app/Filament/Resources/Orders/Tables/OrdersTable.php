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
use Illuminate\Support\Facades\DB;
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
    
    // ⚡ Static caches to avoid repeated DB calls per record
    private static ?int $cachedFollowUpHours = null;
    private static bool $cachedUserIsAdmin = false;
    private static ?bool $cachedCanEditLocked = null;
    private static array $cachedPermissions = [];
    private static ?bool $cachedRequireShipperFirst = null; // ⚡ cached setting
    private static ?object $cachedHeaderSums = null; // ⚡ cached sums for column headers

    /**
     * ⚡ Get Sum of a column from the CURRENTLY DISPLAYED records only
     */
    private static function getHeaderSum(Table $table, string $column): float
    {
        if (self::$cachedHeaderSums === null) {
            try {
                $livewire = $table->getLivewire();
                if (!method_exists($livewire, 'getTableRecords')) {
                    return 0;
                }
                $records = $livewire->getTableRecords();
                $items = ($records instanceof \Illuminate\Contracts\Pagination\Paginator || $records instanceof \Illuminate\Contracts\Pagination\CursorPaginator)
                    ? collect($records->items())
                    : collect($records);
                self::$cachedHeaderSums = (object)[
                    'total_amount' => (float) $items->sum('total_amount'),
                    'fees'         => (float) $items->sum('fees'),
                    'shipper_fees' => (float) $items->sum('shipper_fees'),
                    'cop'          => (float) $items->sum('cop'),
                    'cod'          => (float) $items->sum('cod'),
                ];
            } catch (\Throwable $e) {
                self::$cachedHeaderSums = (object)[
                    'total_amount' => 0, 'fees' => 0, 'shipper_fees' => 0, 'cop' => 0, 'cod' => 0
                ];
            }
        }
        return (float) (self::$cachedHeaderSums->{$column} ?? 0);
    }

    /**
     * ⚡ Get 'require_shipper_collection_first' setting (cached per request)
     */
    private static function requireShipperFirst(): bool
    {
        if (self::$cachedRequireShipperFirst === null) {
            self::$cachedRequireShipperFirst = \App\Models\Setting::get('require_shipper_collection_first', 'yes') === 'yes';
        }
        return self::$cachedRequireShipperFirst;
    }
    
    /**
     * ⚡ Get follow up hours setting (cached per request)
     */
    private static function getFollowUpHours(): int
    {
        if (self::$cachedFollowUpHours === null) {
            self::$cachedFollowUpHours = (int) \App\Models\Setting::get('order_follow_up_hours', 48);
        }
        return self::$cachedFollowUpHours;
    }
    
    /**
     * ⚡ Check user permission (cached per request)
     */
    private static function userCan(string $permission): bool
    {
        if (!isset(self::$cachedPermissions[$permission])) {
            self::$cachedPermissions[$permission] = (bool) auth()->user()?->can($permission);
        }
        return self::$cachedPermissions[$permission];
    }

    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        
        // ⚡ PERF: Eager load roles and permissions to make $user->can() instantaneous (0ms)
        // Without this, Spatie might hit the DB for each permission check.
        if ($user && !$user->relationLoaded('roles')) {
            $user->loadMissing(['roles.permissions', 'permissions']);
        }

        $isAdmin = $user?->isAdmin() ?? false;
        self::$cachedUserIsAdmin = $isAdmin;
        
        // ⚡ PERF: Pre-cache ALL permissions used in per-row closures ONCE
        // These are called inside column closures AND record action visible() that run for EVERY row
        $permissions = [
            'Update:Order',
            'EditLocked:Order',
            'ChangeStatus:Order',
            'ChangeStatusAction:Order',
            'ManageShipperReturnAction:Order',
            'AssignShipperAction:Order',
            'EditExternalCode:Order',
            'EditOrderNotesField:Order',
            'ViewTimelineAction:Order',
            'PrintLabelAction:Order',
            'ManageShipperCollectionAction:Order',
            'ManageCollections:Order',
            'ManageReturns:Order',
            'ManageClientReturnAction:Order',
            'Restore:Order',
            'ForceDelete:Order',
            'View:Order',
            'RestoreAny:Order',
            'DeleteAny:Order',
            'ForceDeleteAny:Order',
            'ViewMyOrdersAction:Order',
            'BarcodeScannerAction:Order',
            'ExportData:Order',
            'Create:Order',
            'BulkChangeStatusAction:Order',
            // Column visibility
            'ViewCodeColumn:Order',
            'ViewExternalCodeColumn:Order',
            'ViewRegistrationDateColumn:Order',
            'ViewShipperDateColumn:Order',
            'ViewRecipientNameColumn:Order',
            'ViewPhoneColumn:Order',
            'ViewAddressColumn:Order',
            'ViewGovernorateColumn:Order',
            'ViewCityColumn:Order',
            'ViewTotalAmountColumn:Order',
            'ViewShippingFeesColumn:Order',
            'ViewShipperCommissionColumn:Order',
            'ViewCompanyShareColumn:Order',
            'ViewCollectionAmountColumn:Order',
            'ViewStatusColumn:Order',
            'ViewStatusNotesColumn:Order',
            'ViewOrderNotesColumn:Order',
            'ViewShipperColumn:Order',
            'ViewClientColumn:Order',
            'ViewDatesColumn:Order',
            'ViewShipperDetails:Order',
            'ViewCustomerDetails:Order',
            'ViewShipperDetailsColumn:Order',
            // Filters
            'ViewDelayedFollowUpFilter:Order',
            'ViewStatusFilter:Order',
            'ViewCollectedFromShipperFilter:Order',
            'ViewReturnedFromShipperFilter:Order',
            'ViewHasReturnFilter:Order',
            'ViewSettledWithClientFilter:Order',
            'ViewReturnedToClientFilter:Order',
        ];
        foreach ($permissions as $p) {
            self::userCan($p);
        }

        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['governorate', 'city', 'shipper', 'client', 'orderStatus'])
                ->latest()
            )
            ->paginationMode(\Filament\Tables\Enums\PaginationMode::Simple)
            ->paginationPageOptions([100])
            ->defaultPaginationPageOption(100)
            // ⚡ searchDebounce: 500ms هو الوضع الطبيعي والاستجابة كويسة
            ->searchDebounce(500)
            ->defaultSort('created_at', 'desc')
            ->filtersFormColumns(3)
            ->extraAttributes([
                'id' => 'orders-table-wrapper',
                'class' => 'orders-table-container',
            ])
            // 🚀 البحث "الطبيعي" لـ Filament مع تفعيل الـ Global على حقول محددة
            ->searchPlaceholder(__('orders.search_placeholder'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('orders.code'))
                    ->color(function ($record) {
                        try {
                            // ⚡ Check governorate specific hours first, then fallback to global setting (cached)
                            $governorateHours = $record->governorate?->follow_up_hours;
                            $limit = ($governorateHours && $governorateHours > 0) 
                                ? (int) $governorateHours 
                                : self::getFollowUpHours();

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
                    ->visible($isAdmin || self::userCan('ViewCodeColumn:Order'))
                    // 📋 Global + Individual: الكود هو المفتاح الرئيسي للبحث
                    ->searchable(isGlobal: true, isIndividual: true),
                TextColumn::make('external_code')
                    ->label(__('orders.external_code'))
                    ->color('warning')
                    ->badge()
                    ->sortable() ->alignCenter()
                    ->visible($isAdmin || self::userCan('ViewExternalCodeColumn:Order'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    // 📋 Global + Individual
                    ->searchable(isGlobal: true, isIndividual: true)
                    ->placeholder(__('orders.external_code_placeholder'))
                    ->action(
                        // ⚡ PERF: self::userCan() uses static cache — NOT per-row auth()->can() call
                        self::userCan('EditExternalCode:Order') ?
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
                    // 📋 Individual only: search box خاص فقط - لا يظهر في السيرش العام
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->alignCenter()
                    ->visible($isAdmin || self::userCan('ViewRegistrationDateColumn:Order'))
                    ->toggleable(),
                TextColumn::make('shipper_date')
                    ->label(__('orders.shipper_date'))
                    ->date('Y-m-d')
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->visible($isAdmin || self::userCan('ViewShipperDateColumn:Order'))
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('orders.recipient_name'))
                    // 📋 Global + Individual
                    ->searchable(isGlobal: true, isIndividual: true)
                    ->alignCenter()
                    ->visible($isAdmin || self::userCan('ViewRecipientNameColumn:Order'))
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
                    ->visible($isAdmin || self::userCan('ViewPhoneColumn:Order'))
                    // 📋 Global + Individual: البحث في أرقام التليفونات
                    ->searchable(
                        isGlobal: true, 
                        isIndividual: true, 
                        query: fn ($query, $search) => $query->where(function($q) use ($search) {
                            $q->where('order.phone', 'like', "%{$search}%")
                              ->orWhere('order.phone_2', 'like', "%{$search}%");
                        })
                    )
                    ->toggleable()->alignCenter(),
                TextColumn::make('address')
                    ->label(__('orders.address'))
                    ->visible($isAdmin || self::userCan('ViewAddressColumn:Order'))
                    ->toggleable()
                    // 📋 Global + Individual
                    ->searchable(isGlobal: true, isIndividual: true)
                    ->limit(length: 50, end: "\n...")  // put special ending instead of (more)
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->address),
                TextColumn::make('governorate.name')
                    // 📋 Individual only - uses whereHas to search related table
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false,
                        query: fn ($query, $search) => $query->whereHas('governorate', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    )
                    ->visible($isAdmin || self::userCan('ViewGovernorateColumn:Order'))
                    ->toggleable()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('city.name')
                    // 📋 Individual only - uses whereHas to search related table
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false,
                        query: fn ($query, $search) => $query->whereHas('city', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    )
                    ->visible($isAdmin || self::userCan('ViewCityColumn:Order'))
                    ->toggleable()
                    ->alignCenter()
                    ->sortable(),
                TextInputColumn::make('total_amount')
                    ->label(fn (Table $table) => __('orders.total_amount') . ' (' . number_format(self::getHeaderSum($table, 'total_amount'), 0) . ')')
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->prefix(__('statuses.currency'))
                    ->sortable()
                    ->toggleable()
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->visible($isAdmin || self::userCan('ViewTotalAmountColumn:Order'))
                    ->afterStateUpdated(fn ($record, $state) => self::updateTotalAmount($record, $state)),

                TextInputColumn::make('fees')
                    ->label(fn (Table $table) => __('orders.shipping_fees') . ' (' . number_format(self::getHeaderSum($table, 'fees'), 0) . ')')
                    ->prefix(__('statuses.currency'))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->sortable()
                    ->visible($isAdmin || self::userCan('ViewShippingFeesColumn:Order'))
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable()
                    ->afterStateUpdated(fn ($record, $state) => self::updateFees($record, $state)),

                TextInputColumn::make('shipper_fees')
                    ->label(fn (Table $table) => __('orders.shipper_commission') . ' (' . number_format(self::getHeaderSum($table, 'shipper_fees'), 0) . ')')
                    ->prefix(__('statuses.currency'))
                    ->disabled(fn ($record) => self::isFieldDisabled($record))
                    ->sortable()
                    ->visible($isAdmin || self::userCan('ViewShipperCommissionColumn:Order'))
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->afterStateUpdated(fn ($record, $state) => self::updateShipperFees($record, $state)),

                TextColumn::make('cop')
                    ->label(fn (Table $table) => __('orders.company_share') . ' (' . number_format(self::getHeaderSum($table, 'cop'), 0) . ')')
                    ->numeric()
                    ->state(fn ($record) => number_format($record->cop, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->visible($isAdmin || self::userCan('ViewCompanyShareColumn:Order'))
                    ->toggleable()
                    ->alignCenter(),

                TextColumn::make('cod')
                    ->label(fn (Table $table) => __('orders.collection_amount') . ' (' . number_format(self::getHeaderSum($table, 'cod'), 0) . ')')
                    ->numeric()
                    ->sortable()
                    ->visible($isAdmin || self::userCan('ViewCollectionAmountColumn:Order'))
                    // 📋 Individual only
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label(__('orders.status'))
                    ->badge()
                    ->color(fn ($record) => strtolower($record->orderStatus?->color ?? 'gray'))
                    ->sortable()
                    ->searchable(isIndividual: true, isGlobal: false)->alignCenter()
                    ->visible($isAdmin || self::userCan('ViewStatusColumn:Order'))
                    ->toggleable()
                    ->extraAttributes(
                        fn ($record) => (!self::$cachedUserIsAdmin && self::isRecordLocked($record))
                            ? []
                            : ['class' => 'cursor-pointer text-primary font-semibold', 'title' => __('statuses.tooltip_change_status')]
                    )
                    ->action(
                        Action::make('changeStatus')
                            ->visible(function ($record) {
                                // ✅ Admin دايماً يقدر يغير الحالة
                                if (self::$cachedUserIsAdmin) {
                                    return true;
                                }
                                // المستخدمين العاديين بيتحققوا من الصلاحية
                                if (!self::userCan('ChangeStatusAction:Order')) {
                                    return false;
                                }
                                // الأوردر مقفول؟
                                if (self::isRecordLocked($record)) {
                                    return false;
                                }
                                // حالة delivered/undelivered بتحتاج صلاحية خاصة
                                if (!self::userCan('EditLocked:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                                    return false;
                                }
                                return true;
                            })
                            ->modalHeading(fn ($record) => '🔄 تغيير حالة الأوردر: #' . $record->code)
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

                                        return $status === self::STATUS_DELIVERED
                                            && (self::$cachedUserIsAdmin || self::userCan('ManageShipperReturnAction:Order'));
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
                                $status = $data['status'];
                                $totalAmount = $data['total_amount'] ?? $record->total_amount;
                                
                                // if status is undelivered set total_amount to 0
                                if ($status === self::STATUS_UNDELIVERED && !isset($data['total_amount'])) {
                                    $totalAmount = 0;
                                }

                                $fees = $record->fees ?? 0;
                                $cod = $totalAmount - $fees;

                                $record->update([
                                    'status' => $status,
                                    'status_note' => $statusNote,
                                    'total_amount' => $totalAmount,
                                    'cod' => $cod,
                                    'has_return' => ! empty($data['has_return']) ? 1 : 0,
                                    'has_return_date' => ! empty($data['has_return']) ? now() : $record->has_return_date,
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
                    ->visible($isAdmin || self::userCan('ViewStatusNotesColumn:Order'))
                    ->extraHeaderAttributes(['style' => 'min-width: 200px'])
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false,
                        query: fn ($query, $search) => $query->where('status_note', 'like', "%{$search}%")
                    )
                    ->color(function ($state) {
                        // Available Filament colors
                        $colors = [
                            'primary',
                            'warning',
                            'danger',
                            'info',
                            'gray',
                        ];

                        $stateString = is_array($state) ? json_encode($state) : (string) ($state ?? '');
                        return $colors[abs(crc32($stateString)) % count($colors)];
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
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('order_note')
                    ->label(__('orders.order_notes'))
                    ->color('success')
                    ->badge()
                    ->sortable()
                    ->alignCenter()
                    ->visible($isAdmin || self::userCan('ViewOrderNotesColumn:Order'))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(isIndividual: true, isGlobal: false)
                    ->placeholder(__('orders.order_notes_placeholder'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->order_note)
                    ->action(
                        // ⚡ PERF: self::userCan() uses static cache — NOT per-row auth()->can() call
                        ($isAdmin || self::userCan('EditOrderNotesField:Order')) ?
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
                    ->visible($isAdmin || self::userCan('ViewShipperColumn:Order'))
                    ->placeholder('➕ عين كابتن')
                    ->color('primary')
                    ->weight('bold')
                    ->description(function ($record) {
                        // ⚡ FIX: use null-safe operator to prevent PHP error when shipper is null
                        return $record->shipper?->phone;
                    })
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false,
                        query: fn ($query, $search) => $query->whereHas('shipper', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    )
                    ->toggleable()  
                    ->sortable()
                    ->action(
                        // ⚡ PERF: self::userCan() uses static cache — NOT per-row auth()->can() call
                        self::userCan('AssignShipperAction:Order') ?

                        Action::make('assignShipper')
                            ->modalHeading('🚚 تعيين كابتن للأوردر')
                            ->modalWidth('sm')
                            ->form([
                                Select::make('shipper_id')
                                    ->label('اختار الكابتن')
                                    ->relationship(
                                        name: 'shipper',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->role('shipper')
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
                    ->visible($isAdmin || self::userCan('ViewClientColumn:Order'))
                    ->searchable(
                        isIndividual: true,
                        isGlobal: false,
                        query: fn ($query, $search) => $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    )
                    ->alignCenter()
                    ->toggleable(),
          

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->visible($isAdmin || self::userCan('ViewDatesColumn:Order'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make()
                    ->label(__('orders.filters.deleted_orders'))
                    ->placeholder(__('orders.filters.active_orders'))
                    ->trueLabel(__('orders.filters.deleted_only'))
                    ->falseLabel(__('orders.filters.all_with_deleted'))
                    ->visible(fn() => $isAdmin || self::userCan('RestoreAny:Order') || self::userCan('DeleteAny:Order')),

                \Filament\Tables\Filters\SelectFilter::make('follow_up_status')
                    ->label(__('orders.filters.delay_follow_up'))
                    ->visible($isAdmin || self::userCan('ViewDelayedFollowUpFilter:Order'))
                    ->options([
                        'delayed' => __('orders.filters.delayed'),
                        'on_time' => __('orders.filters.on_time'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        // ⚡ PERF: use static cached getFollowUpHours() instead of Setting::get() on every filter call
                        $globalLimit = self::getFollowUpHours();
                        
                        // Calculate dynamic limit based on governorate or fallback to global
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
                    ->visible($isAdmin || self::userCan('ViewStatusFilter:Order'))
                    ->options([
                        self::STATUS_OUT_FOR_DELIVERY => '🚚 ' . __('app.out_for_delivery'),
                        self::STATUS_DELIVERED => '✅ ' . __('app.delivered'),
                        self::STATUS_UNDELIVERED => '❌ ' . __('app.undelivered'),
                        self::STATUS_HOLD => '⏸️ ' . __('app.hold'),
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('collected_shipper')
                    ->label(__('orders.filters.collected_from_shipper'))
                    ->visible($isAdmin || self::userCan('ViewCollectedFromShipperFilter:Order'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('return_shipper')
                    ->label(__('orders.filters.returned_from_shipper'))
                    ->visible($isAdmin || self::userCan('ViewReturnedFromShipperFilter:Order'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('has_return')
                    ->label(__('orders.filters.has_return'))
                    ->visible($isAdmin || self::userCan('ViewHasReturnFilter:Order'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('collected_client')
                    ->label(__('orders.filters.settled_with_client'))
                    ->visible($isAdmin || self::userCan('ViewSettledWithClientFilter:Order'))
                    ->placeholder(__('statuses.all'))
                    ->trueLabel(__('statuses.yes'))
                    ->falseLabel(__('statuses.no')),
                \Filament\Tables\Filters\TernaryFilter::make('return_client')
                    ->label(__('orders.filters.returned_to_client'))
                    ->visible($isAdmin || self::userCan('ViewReturnedToClientFilter:Order'))
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
                        ->visible($isAdmin || self::userCan('ExportSelectedAction:Order'))
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
                        ->visible($isAdmin || self::userCan('ExportExternalCodesAction:Order'))
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
                        ->visible($isAdmin || self::userCan('PrintLabelsAction:Order'))
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
                        ->visible($isAdmin || self::userCan('AssignShipperAction:Order'))
                        ->form([
                            Select::make('shipper_id')
                                ->label(__('orders.shipper_select_label'))
                                ->relationship(
                                    name: 'shipper',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->role('shipper')
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
                        ->visible(fn() => $isAdmin || self::userCan('BulkChangeStatusAction:Order'))
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
                        ->label('تحصيل من المندوب')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn() => $isAdmin || self::userCan('ManageShipperCollectionAction:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('تحصيل المبالغ من المندوبين')
                        ->modalDescription('سيتم إنشاء فاتورة تحصيل (أو الإضافة لفاتورة معلقة) لكل مندوب تم اختيار طلبات له.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $service = new CollectedShipperService();
                            
                            // تجميع الطلبات حسب المندوب
                            $groupedByShipper = $records->groupBy('shipper_id');
                            
                            $successCount = 0;
                            $shippersInvolved = 0;

                            foreach ($groupedByShipper as $shipperId => $shipperOrders) {
                                if (!$shipperId) continue;
                                
                                $shippersInvolved++;
                                
                                // فلترة الطلبات الصالحة للتحصيل فقط
                                $validOrderIds = $shipperOrders->filter(function ($order) use ($service) {
                                    return $service->isOrderEligibleForCollection($order);
                                })->pluck('id')->toArray();

                                if (empty($validOrderIds)) continue;

                                // البحث عن تحصيل معلق لهذا المندوب
                                $existingCollection = \App\Models\CollectedShipper::where('shipper_id', $shipperId)
                                    ->where('status', \App\Enums\CollectingStatus::PENDING->value)
                                    ->first();

                                if ($existingCollection) {
                                    // إضافة الطلبات للتحصيل الموجود
                                    $service->addOrdersToCollection($existingCollection, $validOrderIds);
                                } else {
                                    // إنشاء تحصيل جديد
                                    $service->createCollection($shipperId, $validOrderIds);
                                }
                                
                                $successCount += count($validOrderIds);
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title("✅ تم التحصيل بنجاح")
                                    ->body("تمت معالجة {$successCount} طلب لعدد {$shippersInvolved} مندوب.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("⚠️ لم يتم تحصيل أي طلبات")
                                    ->body("تأكد من أن الطلبات المختارة في حالة (تم التسليم) أو (غير مستلم) ولم يتم تحصيلها مسبقاً.")
                                    ->warning()
                                    ->send();
                            }
                        }),
                    
                    BulkAction::make('uncollectShipper')
                        ->label('Cancel Collection')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->visible(fn() => $isAdmin || self::userCan('ManageShipperCollectionAction:Order'))
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
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ManageClientCollectionAction:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Collect for Client')
                        ->modalDescription('Are you sure you want to collect amounts for the client for the selected orders?')
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $requireShipperFirst = self::requireShipperFirst(); // ⚡ PERF: cached
                            
                            $clientId = null;
                            $count = 0;
                            $skipped = 0;
                            $totalAmount = 0;
                            $totalFees = 0;
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
                                if ($record->status === self::STATUS_DELIVERED) {
                                    $totalAmount += $record->total_amount ?? 0;
                                }
                                $totalFees += $record->fees ?? 0;
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
                                        'total_amount' => $existingCollection->total_amount + $totalAmount,
                                        'fees' => $existingCollection->fees + $totalFees,
                                        'number_of_orders' => $existingCollection->number_of_orders + $count,
                                        'notes' => ($existingCollection->notes ?? '') . "\nAdded {$count} orders on " . now()->format('Y-m-d H:i'),
                                    ]);

                                    $collection = $existingCollection;
                                } else {
                                    // Create new collection record with pending status
                                    $collection = \App\Models\CollectedClient::create([
                                        'client_id' => $clientId,
                                        'collection_date' => now(),
                                        'total_amount' => $totalAmount,
                                        'fees' => $totalFees,
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
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ManageClientCollectionAction:Order'))
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
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ManageShipperReturnAction:Order'))
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
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->visible(fn() => $isAdmin || self::userCan('ManageClientReturnAction:Order'))
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
                        ->visible(fn() => $isAdmin || self::userCan('DeleteAny:Order'))
                        ->requiresConfirmation()
                        ->modalHeading('Delete Orders')
                        ->modalDescription('Are you sure you want to delete selected orders? You can restore them later.')
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-uturn-up')
                        ->color('danger')
                        ->visible(fn() => $isAdmin || self::userCan('RestoreAny:Order'))
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
                        ->visible(fn() => $isAdmin || self::userCan('ForceDeleteAny:Order'))
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
            ->filtersFormMaxHeight('400px');
    }

    private static function getHeaderActions(): array
    {
        $isAdmin = self::$cachedUserIsAdmin; // ⚡ restore from static cache
        return [
            // Display shipper orders
            Action::make('myOrders')
                ->label('My Orders')
                ->color('info')
                ->visible(fn() => $isAdmin || self::userCan('ViewMyOrdersAction:Order'))
                ->modalHeading('My Orders - Out for Delivery')
                ->modalWidth('7xl')
                ->modalContent(function () {
                    $user = auth()->user();
                    $orders = Order::select([
                            'order.id', 'order.code', 'order.name', 'order.phone', 'order.phone_2',
                            'order.address', 'order.total_amount', 'order.fees', 'order.shipper_fees',
                            'order.cod', 'order.allow_open', 'order.shipper_date',
                            'order.governorate_id', 'order.city_id', 'order.status',
                        ])
                        ->where('shipper_id', $user->id)
                        ->where('status', self::STATUS_OUT_FOR_DELIVERY)
                        ->with([
                            'governorate:id,name',
                            'city:id,name',
                        ])
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
                ->visible(fn() => $isAdmin || self::userCan('BarcodeScannerAction:Order'))
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
                                $query = Order::with(['client', 'shipper', 'governorate', 'city']);
                                
                                $user = auth()->user();
                                if ($user->isShipper()) {
                                    $query->where('shipper_id', $user->id)
                                          ->where('collected_shipper', false);
                                }

                                $order = $query->where(function($q) use ($state) {
                                    $q->where('code', $state)
                                      ->orWhere('code', 'like', "%{$state}%");
                                })->first();
                                
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
                            if (self::userCan('ChangeStatus:Order')) {
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
                                if (self::userCan('ManageCollections:Order')) {
                                    if ($orderData['collected_shipper']) {
                                        $options['uncollect_shipper'] = 'Cancel Shipper Collection';
                                    } else {
                                        $options['collect_shipper'] = 'Collect from Shipper';
                                    }
                                }                                                      // Collect for Client
                                if (self::userCan('ManageCollections:Order')) {
                                    if ($orderData['collected_client']) {
                                        $options['uncollect_client'] = 'Cancel Client Collection';
                                    } else {
                                        $options['collect_client'] = 'Collect for Client';
                                    }
                                }                                                      // Returns
                                if (self::userCan('ManageReturns:Order')) {
                                    if ($orderData['has_return']) {
                                        $options['cancel_return_shipper'] = 'Cancel Shipper Return';
                                    } else {
                                        $options['mark_return_shipper'] = 'Activate Shipper Return';
                                    }
                                }                                                      // Client Return
                                if (self::userCan('ManageReturns:Order')) {
                                    $options['toggle_return_client'] = $orderData['return_client'] ?? false ? 'Cancel Client Return' : 'Activate Client Return';
                                }                                                      // Print Receipt
                                if (self::userCan('PrintLabelAction:Order')) {
                                    $options['print_label'] = 'Print Shipping Label';
                                }                                                      // Timeline
                                if (self::userCan('ViewTimelineAction:Order')) {
                                    $options['view_timeline'] = 'View Timeline';
                                }                          
                            return $options;
                        })
                        ->default('view')
                        ->required()
                        ->visible(fn ($get) => $get('order_id') !== null)
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->extraAttributes([
                            'style' => '
                                background-color: #ffffff !important;
                                color: #111827 !important;
                                border: 2px solid #6366f1 !important;
                                border-radius: 8px !important;
                                font-size: 1rem !important;
                                font-weight: 600 !important;
                                padding: 0.5rem 1rem !important;
                            ',
                        ])
                        ->extraInputAttributes([
                            'style' => 'background: white !important; color: #111827 !important;',
                        ]),
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
                ->visible(fn() => $isAdmin || self::userCan('ExportData:Order'))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('limit')
                        ->label('Number of Orders')
                        ->placeholder('Leave empty to Export All')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('⚠️ Exporting all records on large datasets may take time. Recommend max 10,000.'),
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
                ->visible(fn() => $isAdmin || self::userCan('Create:Order'))
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
                        ->relationship(
                            name: 'client',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->role('client')
                        )
                        ->searchable()
                        ->placeholder('Select Client or leave empty'),

                    Select::make('shipper_id')
                        ->label('Shipper (Optional)')
                        ->relationship(
                            name: 'shipper',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->role('shipper')
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
                ->visible(fn() => $isAdmin || self::userCan('Create:Order'))
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
        $isAdmin = self::$cachedUserIsAdmin; // ⚡ restore from static cache
        return [
            ActionGroup::make([
                Action::make('copyOrder')
                    ->label('نسخ البيانات')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('info')
                    ->action(fn () => Notification::make()
                        ->title('تم نسخ البيانات بنجاح')
                        ->success()
                        ->send())
                    ->extraAttributes(fn ($record) => [
                        'onclick' => "
                            const text = `كود: {$record->code}\nاسم المستلم: {$record->name}\nالتليفون: {$record->phone}\nالمحافظة: " . ($record->governorate?->name ?? '-') . "\nالمدينة: " . ($record->city?->name ?? '-') . "\nالعنوان: {$record->address}\nالمبلغ الإجمالي: {$record->total_amount}`;navigator.clipboard.writeText(text);"
                    ]),

                // WhatsApp Action
                Action::make('whatsapp')
                    ->label('واتساب للعميل')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn ($record) => "https://wa.me/+20" . ltrim($record->phone, '0'), shouldOpenInNewTab: true)
                    ->visible(fn ($record) => !empty($record->phone)),

                // Shipper cannot edit if order is delivered
                // Client is forbidden from editing completely
                // Admin can edit anything
                EditAction::make()->visible(function($record) {
                    // ⚡ PERF: all can() use cached static permissions
                    if (!self::userCan('Update:Order')) {
                        return false;
                    }
                    if (self::isRecordLocked($record)) {
                        return false;
                    }
                    if (!self::userCan('EditLocked:Order') && in_array($record->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                        return false;
                    }
                    return true;
                }),

                Action::make('timeline')
                    ->visible(fn() => $isAdmin || self::userCan('ViewTimelineAction:Order'))
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
                    ->visible(fn() => $isAdmin || self::userCan('PrintLabelAction:Order'))
                    ->url(fn($record) => route('orders.print-label', $record->id))
                    ->openUrlInNewTab(),
                
                // Collection actions (no nested ActionGroup to avoid CSS issues)
                Action::make('toggleCollectedShipper')
                        ->label(fn($record) => $record->collected_shipper ? '❌ إلغاء التحصيل من الكابتن' : '✅ تم التحصيل من الكابتن')
                        ->icon('heroicon-o-truck')
                        ->color(fn($record) => $record->collected_shipper ? 'danger' : 'success')
                        ->visible(fn() => $isAdmin || self::userCan('ManageShipperCollectionAction:Order'))
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
                                    'total_amount' => $existingCollection->total_amount + ($record->status === self::STATUS_DELIVERED ? ($record->total_amount ?? 0) : 0),
                                    'shipper_fees' => $existingCollection->shipper_fees + ($record->shipper_fees ?? 0),
                                    'number_of_orders' => $existingCollection->number_of_orders + 1,
                                    'notes' => ($existingCollection->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $collection = $existingCollection;
                            } else {
                                // Create new collection with pending status
                                $collection = \App\Models\CollectedShipper::create([
                                    'shipper_id' => $record->shipper_id,
                                    'collection_date' => now(),
                                    'total_amount' => $record->status === self::STATUS_DELIVERED ? ($record->total_amount ?? 0) : 0,
                                    'shipper_fees' => $record->shipper_fees ?? 0,
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
                        ->visible(fn() => $isAdmin || self::userCan('ManageCollections:Order'))
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
                                    'total_amount' => $existingCollection->total_amount + ($record->status === self::STATUS_DELIVERED ? ($record->total_amount ?? 0) : 0),
                                    'fees' => $existingCollection->fees + ($record->fees ?? 0),
                                    'number_of_orders' => $existingCollection->number_of_orders + 1,
                                    'notes' => ($existingCollection->notes ?? '') . "\nAdded order #{$record->code} on " . now()->format('Y-m-d H:i'),
                                ]);

                                $collection = $existingCollection;
                            } else {
                                // Create new collection with pending status
                                $collection = \App\Models\CollectedClient::create([
                                    'client_id' => $record->client_id,
                                    'collection_date' => now(),
                                    'total_amount' => $record->status === self::STATUS_DELIVERED ? ($record->total_amount ?? 0) : 0,
                                    'fees' => $record->fees ?? 0,
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
                        ->visible(fn() => $isAdmin || self::userCan('ManageReturns:Order'))
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
                        ->visible(fn() => $isAdmin || self::userCan('ManageReturns:Order'))
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

                
                // ♻️ استرجاع الأوردر الDeleted
                Action::make('restore')
                    ->label('♻️ استرجاع')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn($record) => self::userCan('Restore:Order') && $record->trashed())
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
                    ->visible(fn($record) => self::userCan('ForceDelete:Order') && $record->trashed())
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
          ])->visible(fn() => $isAdmin || self::userCan('View:Order')),
        ];
    }
    private static function getOrderStatusGroup(): ColumnGroup
    {
        // ⚡ PERF: Calculate ALL permissions ONCE here, not per-row
        $user = auth()->user();
        $canViewOrder       = (bool) $user?->can('View:Order');
        $canViewShipper     = (bool) $user?->can('ViewShipperDetails:Order');
        $canViewCustomer    = (bool) $user?->can('ViewCustomerDetails:Order');

        $statusFields = [
            'collected_shipper' => ['label' => 'تحصيل كابتن', 'visible' => $canViewShipper],
            'return_shipper'    => ['label' => 'مرتجع كابتن', 'visible' => $canViewShipper],
            'has_return'        => ['label' => 'فيه مرتجع', 'visible' => $canViewOrder],
            'collected_client'  => ['label' => 'تسوية عميل', 'visible' => $canViewCustomer],
            'return_client'     => ['label' => 'مرتجع عميل', 'visible' => $canViewCustomer],
        ];

        $canManageShipperCollection = (bool) $user?->can('ManageShipperCollectionAction:Order');
        $canManageClientCollection  = (bool) $user?->can('ManageCollections:Order');
        $canManageReturns           = (bool) $user?->can('ManageReturns:Order');
        $isAdminUser                = self::$cachedUserIsAdmin;

        // Map each field to the permission that controls it
        $fieldPermissions = [
            'collected_shipper' => $isAdminUser || $canManageShipperCollection,
            'return_shipper'    => $isAdminUser || $canManageReturns,
            'has_return'        => $isAdminUser || $canManageReturns,
            'collected_client'  => $isAdminUser || $canManageClientCollection,
            'return_client'     => $isAdminUser || $canManageReturns,
        ];

        $columns = [];
        foreach ($statusFields as $field => $config) {
            $columns[] = TextColumn::make($field)
                ->label(new \Illuminate\Support\HtmlString(
                    view('filament.tables.columns.status-filter-header', [
                        'label' => $config['label'],
                        'field' => $field,
                    ])->render()
                ))
                ->badge()
                ->toggleable()
                ->visible($config['visible'])  // ⚡ pre-calculated, not per-row
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
        
        $record->total_amount = $state + $record->fees;
        $record->recalculateFinancials(); // سيقوم بحساب COD وتحديثه ليطابق Net Fees
        $record->save();
    }

    private static function isFieldDisabled($record): bool
    {
        // ⚡ Use cached permission check
        if (!self::userCan('Update:Order')) {
            return true;
        }

        return self::isRecordLocked($record);
    }

    private static function isRecordLocked($record): bool
    {
        // ⚡ Cache EditLocked permission check
        if (self::$cachedCanEditLocked === null) {
            self::$cachedCanEditLocked = self::userCan('EditLocked:Order');
        }
        
        if (self::$cachedCanEditLocked) {
            return false;
        }

        return $record->collected_client_date !== null
            || $record->collected_shipper_date !== null;
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
