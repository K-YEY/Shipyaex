<?php

namespace App\Filament\Resources\Orders\Tables\Concerns;

use App\Models\Order;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

trait HasOrdersTableColumns
{
    public static function getColumns(): array
    {
        $isAdmin = self::$cachedUserIsAdmin;

        return [
            TextColumn::make('code')
                ->label(__('orders.code'))
                ->color(function ($record) {
                    try {
                        if (!$record) return 'info';
                        
                        $governorateHours = $record->governorate?->follow_up_hours;
                        $limit = ($governorateHours && $governorateHours > 0) 
                            ? (int) $governorateHours 
                            : self::getFollowUpHours();

                        if (in_array($record->status, [self::STATUS_OUT_FOR_DELIVERY])) {
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
                ->searchable(isGlobal: false, isIndividual: true),

            TextColumn::make('external_code')
                ->label(__('orders.external_code'))
                ->color('warning')
                ->badge()
                ->sortable() ->alignCenter()
                ->visible($isAdmin || self::userCan('ViewExternalCodeColumn:Order'))
                ->toggleable(isToggledHiddenByDefault: false)
                ->searchable(isGlobal: false, isIndividual: true)
                ->placeholder(__('orders.external_code_placeholder'))
                ->action(
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
                    : null
                ),

            TextColumn::make('created_at')
                ->label(__('orders.registration_date'))
                ->date('Y-m-d')
                ->sortable()
                ->searchable(isIndividual: true, isGlobal: false)
                ->alignCenter()
                ->visible($isAdmin || self::userCan('ViewRegistrationDateColumn:Order'))
                ->toggleable(),

            TextColumn::make('shipper_date')
                ->label(__('orders.shipper_date'))
                ->date('Y-m-d')
                ->searchable(isIndividual: true, isGlobal: false)
                ->visible($isAdmin || self::userCan('ViewShipperDateColumn:Order'))
                ->alignCenter()
                ->sortable(),

            TextColumn::make('name')
                ->label(__('orders.recipient_name'))
                ->searchable(isGlobal: false, isIndividual: true)
                ->alignCenter()
                ->visible($isAdmin || self::userCan('ViewRecipientNameColumn:Order'))
                ->toggleable(),

            TextColumn::make('customer_phones')
                ->label(__('orders.phone'))
                ->getStateUsing(
                    fn ($record) => collect([$record->phone, $record->phone_2])
                        ->filter()
                        ->map(fn ($phone) => e($phone))
                        ->join('<br>')
                )
                ->html()
                ->visible($isAdmin || self::userCan('ViewPhoneColumn:Order'))
                ->searchable(
                    isGlobal: false, 
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
                ->searchable(isGlobal: false, isIndividual: true)
                ->limit(length: 50, end: "\n...")
                ->alignCenter()
                ->tooltip(fn ($record) => $record?->address),

            TextColumn::make('governorate.name')
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
                ->searchable(isIndividual: true, isGlobal: false)
                ->visible($isAdmin || self::userCan('ViewTotalAmountColumn:Order'))
                ->afterStateUpdated(fn ($record, $state) => self::updateTotalAmount($record, $state)),

            TextInputColumn::make('fees')
                ->label(fn (Table $table) => __('orders.shipping_fees') . ' (' . number_format(self::getHeaderSum($table, 'fees'), 0) . ')')
                ->prefix(__('statuses.currency'))
                ->disabled(fn ($record) => self::isFieldDisabled($record))
                ->sortable()
                ->visible($isAdmin || self::userCan('ViewShippingFeesColumn:Order'))
                ->searchable(isIndividual: true, isGlobal: false)
                ->toggleable()
                ->afterStateUpdated(fn ($record, $state) => self::updateFees($record, $state)),

            TextInputColumn::make('shipper_fees')
                ->label(fn (Table $table) => __('orders.shipper_commission') . ' (' . number_format(self::getHeaderSum($table, 'shipper_fees'), 0) . ')')
                ->prefix(__('statuses.currency'))
                ->disabled(fn ($record) => self::isFieldDisabled($record))
                ->sortable()
                ->visible($isAdmin || self::userCan('ViewShipperCommissionColumn:Order'))
                ->searchable(isIndividual: true, isGlobal: false)
                ->afterStateUpdated(fn ($record, $state) => self::updateShipperFees($record, $state)),

            TextColumn::make('cop')
                ->label(fn (Table $table) => __('orders.company_share') . ' (' . number_format(self::getHeaderSum($table, 'cop'), 0) . ')')
                ->numeric()
                ->state(fn ($record) => number_format($record->cop, 2) . ' ' . __('statuses.currency'))
                ->sortable()
                ->searchable(isIndividual: true, isGlobal: false)
                ->visible($isAdmin || self::userCan('ViewCompanyShareColumn:Order'))
                ->toggleable()
                ->alignCenter(),

            TextColumn::make('cod')
                ->label(fn (Table $table) => __('orders.collection_amount') . ' (' . number_format(self::getHeaderSum($table, 'cod'), 0) . ')')
                ->numeric()
                ->sortable()
                ->visible($isAdmin || self::userCan('ViewCollectionAmountColumn:Order'))
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
                ->action(self::getChangeStatusAction()),

            TextColumn::make('status_note')
                ->label(__('orders.status_notes'))
                ->badge()                    
                ->alignCenter()
                ->visible($isAdmin || self::userCan('ViewStatusNotesColumn:Order'))
                ->extraHeaderAttributes(['style' => 'min-width: 200px'])
                ->searchable(isIndividual: true, isGlobal: false, query: fn ($query, $search) => $query->where('status_note', 'like', "%{$search}%"))
                ->color(function ($state) {
                    $colors = ['primary', 'warning', 'danger', 'info', 'gray'];
                    $stateString = is_array($state) ? json_encode($state) : (string) ($state ?? '');
                    return $colors[abs(crc32($stateString)) % count($colors)];
                })
                ->formatStateUsing(function ($state) {
                    if (empty($state)) return '-';
                    if (is_string($state)) {
                        $decoded = json_decode($state, true);
                        $state = is_array($decoded) ? $decoded : [$state];
                    }
                    return $state;
                }),
        ];
    }

    protected static function getChangeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->visible(function ($record) {
                if (self::$cachedUserIsAdmin) return true;
                if (!self::userCan('ChangeStatusAction:Order')) return false;
                if (self::isRecordLocked($record)) return false;
                if (!self::userCan('EditLocked:Order') && in_array($record?->status, [self::STATUS_DELIVERED, self::STATUS_UNDELIVERED])) {
                    return false;
                }
                return true;
            })
            ->modalHeading(fn ($record) => '🔄 تغيير حالة الأوردر: #' . ($record?->code ?? ''))
            ->modalWidth('2xl')
            ->schema([
                Select::make('status')
                    ->label(__('statuses.new_status_label'))
                    ->options(fn() => \App\Models\OrderStatus::active()->ordered()->pluck('name', 'slug')->toArray())
                    ->default(fn ($record) => $record?->status)
                    ->reactive()
                    ->required(),

                TagsInput::make('status_note')
                    ->label(__('statuses.refused_reasons_notes_label'))
                    ->suggestions(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $selectedStatus = $get('status');
                        if (!$selectedStatus) return [];
                        $orderStatus = \App\Models\OrderStatus::where('slug', $selectedStatus)->with('refusedReasons')->first();
                        return $orderStatus ? $orderStatus->refusedReasons()->active()->ordered()->pluck('name')->toArray() : [];
                    })
                    ->default(fn ($record) => (array) ($record?->status_note ?? []))
                    ->columnSpanFull(),

                Toggle::make('has_return')
                    ->label(__('statuses.is_return_label'))
                    ->visible(function (\Filament\Schemas\Components\Utilities\Get $get, $record) {
                        $status = $get('status') ?? $record?->status;
                        return $status === self::STATUS_DELIVERED && (self::$cachedUserIsAdmin || self::userCan('ManageShipperReturnAction:Order'));
                    }),

                \Filament\Forms\Components\TextInput::make('total_amount')
                    ->label(__('statuses.total_amount_label'))
                    ->numeric()
                    ->prefix(__('statuses.currency'))
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get, $record) => ($get('status') ?? $record?->status) === self::STATUS_DELIVERED),
            ])
            ->action(function ($record, $data) {
                $status = $data['status'];
                $totalAmount = $data['total_amount'] ?? $record->total_amount;
                if ($status === self::STATUS_UNDELIVERED && !isset($data['total_amount'])) $totalAmount = 0;
                
                $cod = $totalAmount - ($record->fees ?? 0);
                $record->update([
                    'status' => $status,
                    'status_note' => $data['status_note'] ?? [],
                    'total_amount' => $totalAmount,
                    'cod' => $cod,
                    'has_return' => ! empty($data['has_return']) ? 1 : 0,
                    'has_return_date' => ! empty($data['has_return']) ? now() : $record->has_return_date,
                ]);

                Notification::make()->title(__('statuses.success_update_title'))->success()->send();
            });
    }
}
