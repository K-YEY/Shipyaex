<?php

namespace App\Filament\Resources\CollectedShippers\Tables;

use App\Models\CollectedShipper;
use App\Models\Order;
use App\Models\User;
use App\Services\CollectedShipperService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CollectedShippersTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        // Use permission check instead of role
        $canManage = $user->can('update_collected_shipper');

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:CollectedShipper'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('shipper.name')
                    ->label('المندوب')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:CollectedShipper'))
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-truck')
                    ->weight('bold'),

                TextColumn::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCollectionDateColumn:CollectedShipper'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('number_of_orders')
                    ->label('عدد الطلبات')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountColumn:CollectedShipper'))
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewTotalAmountColumn:CollectedShipper'))
                    ->state(fn ($record) => number_format($record->total_amount, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->color('primary'),

                TextColumn::make('shipper_fees')
                    ->label('عمولة المندوب')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewFeesColumn:CollectedShipper'))
                    ->state(fn ($record) => number_format($record->shipper_fees, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->color('warning'),

                TextColumn::make('net_amount')
                    ->label('صافي المندوب')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNetAmountColumn:CollectedShipper'))
                    ->state(fn ($record) => number_format($record->net_amount, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:CollectedShipper'))
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:CollectedShipper'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:CollectedShipper'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),

                SelectFilter::make('shipper_id')
                    ->label('المندوب')
                    ->options(function () {
                        // جلب Shippers اللي عندهم أوردرات محصلة فقط
                        return User::role('shipper')
                            ->whereHas('shipperOrders', function ($query) {
                                $query->where('collected_shipper', true);
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:CollectedShipper')),

                Filter::make('collection_date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('collection_date', '>=', $date))
                            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('collection_date', '<=', $date));
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('عرض'),

                    EditAction::make()
                        ->label('تعديل')
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Update:CollectedShipper') && $record->status === 'pending'),

                    // عرض الطلبات
                    Action::make('viewOrders')
                        ->label('عرض الطلبات')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersAction:CollectedShipper'))
                        ->modalHeading(fn ($record) => "طلبات التحصيل رقم #{$record->id}")
                        ->modalContent(fn ($record) => view('filament.collecting.orders-modal', [
                            'orders' => $record->orders,
                            'type' => 'shipper',
                        ])),

                    // اعتماد التحصيل
                    Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Approve:CollectedShipper') && $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('اعتماد التحصيل')
                        ->modalDescription('هل أنت متأكد من اعتماد هذا التحصيل؟ سيتم تأكيد استلام المبالغ لجميع الأوردرات المرتبطة.')
                        ->action(function ($record) {
                            $service = new CollectedShipperService();
                            $service->approveCollection($record);

                            Notification::make()
                                ->title('تم اعتماد التحصيل بنجاح ✅')
                                ->success()
                                ->send();
                        }),

                    // إلغاء التحصيل
                    Action::make('cancel')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Cancel:CollectedShipper') && $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء التحصيل')
                        ->modalDescription('هل أنت متأكد من إلغاء هذا التحصيل؟ سيتم فك ارتباط جميع الأوردرات.')
                        ->action(function ($record) {
                            $service = new CollectedShipperService();
                            $service->cancelCollection($record);

                            Notification::make()
                                ->title('تم إلغاء التحصيل ❌')
                                ->danger()
                                ->send();
                        }),

                    // طباعة الفاتورة
                    Action::make('printInvoice')
                        ->label('طباعة الفاتورة')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('PrintInvoice:CollectedShipper') && $record->status === 'completed')
                        ->url(fn ($record) => route('collecting.shipper.invoice', $record->id))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // اعتماد جماعي
                    BulkAction::make('bulkApprove')
                        ->label('اعتماد المختار')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('Approve:CollectedShipper'))
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $service = new CollectedShipperService();
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $service->approveCollection($record);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("تم اعتماد {$count} توريدة بنجاح ✅")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->label('حذف المختار')
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('DeleteAny:CollectedShipper')),
                ]),
            ])
            ->striped()
            ->poll('30s');
    }
}
