<?php

namespace App\Filament\Resources\CollectedClients\Tables;

use App\Models\CollectedClient;
use App\Services\CollectedClientService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CollectedClientsExport;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CollectedClientsTable
{
    private static ?object $cachedHeaderSums = null;

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
                    'fees'             => (float) $items->sum('fees'),
                    'total_amount'     => (float) $items->sum('total_amount'),
                    'net_amount'       => (float) $items->sum('net_amount'),
                    'number_of_orders' => (float) $items->sum('number_of_orders'),
                ];
            } catch (\Throwable $e) {
                self::$cachedHeaderSums = (object)[
                    'fees' => 0, 'total_amount' => 0, 'net_amount' => 0, 'number_of_orders' => 0
                ];
            }
        }
        return (float) (self::$cachedHeaderSums->{$column} ?? 0);
    }

    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        // Use permission check instead of role
        $canManage = $user->can('update_collected_client');

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:CollectedClient'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewClientColumn:CollectedClient'))
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('bold'),

                TextColumn::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewCollectionDateColumn:CollectedClient'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('number_of_orders')
                    ->label(fn (Table $table) => 'عدد الطلبات' . ' (' . number_format(self::getHeaderSum($table, 'number_of_orders'), 0) . ')')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountColumn:CollectedClient'))
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),


                TextColumn::make('total_amount')
                    ->label(fn (Table $table) => 'الإجمالي' . ' (' . number_format(self::getHeaderSum($table, 'total_amount'), 0) . ')')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewTotalAmountColumn:CollectedClient'))
                    ->state(fn ($record) => number_format($record->total_amount, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->color('primary'),


                TextColumn::make('fees')
                    ->label(fn (Table $table) => 'مصاريف الشحن' . ' (' . number_format(self::getHeaderSum($table, 'fees'), 0) . ')')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewFeesColumn:CollectedClient'))
                    ->state(fn ($record) => number_format($record->fees, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->color('warning'),


                TextColumn::make('net_amount')
                    ->label(fn (Table $table) => 'الصافي' . ' (' . number_format(self::getHeaderSum($table, 'net_amount'), 0) . ')')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewNetAmountColumn:CollectedClient'))
                    ->state(fn ($record) => number_format($record->net_amount, 2) . ' ' . __('statuses.currency'))
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),


                TextColumn::make('status')
                    ->label('الحالة')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:CollectedClient'))
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:CollectedClient'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewDatesColumn:CollectedClient'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),

                SelectFilter::make('client')
                    ->label('العميل')
                    ->relationship('client', 'name', fn($query) => $query->role('client'))
                    ->searchable()
                    ->preload()
                    ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ViewClientColumn:CollectedClient')),

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
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Update:CollectedClient') && $record->status === 'pending'),

                    // عرض الطلبات
                    Action::make('viewOrders')
                        ->label('عرض الطلبات')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersAction:CollectedClient'))
                        ->modalHeading(fn ($record) => "طلبات التحصيل رقم #{$record->id}")
                        ->modalContent(fn ($record) => view('filament.collecting.orders-modal', [
                            'orders' => $record->orders,
                            'type' => 'client',
                        ])),

                    // اعتماد التحصيل
                    Action::make('approve')
                        ->label('اعتماد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Approve:CollectedClient') && $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('اعتماد التحصيل')
                        ->modalDescription('هل أنت متأكد من اعتماد هذا التحصيل؟')
                        ->action(function ($record) {
                            $service = new CollectedClientService();
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
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Cancel:CollectedClient') && $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء التحصيل')
                        ->modalDescription('هل أنت متأكد من إلغاء هذا التحصيل؟')
                        ->action(function ($record) {
                            $service = new CollectedClientService();
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
                        ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('PrintInvoice:CollectedClient') && $record->status === 'completed')
                        ->url(fn ($record) => route('collecting.client.invoice', $record->id))
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
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('Approve:CollectedClient'))
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $service = new CollectedClientService();
                            $count = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $service->approveCollection($record);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("تم اعتماد {$count} تسوية بنجاح ✅")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->label('حذف المختار')
                        ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('DeleteAny:CollectedClient')),

                    BulkAction::make('exportExcel')
                        ->label('استخراج Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(fn (Collection $records) => Excel::download(
                            new CollectedClientsExport(null, $records->pluck('id')->toArray()),
                            'collected-clients-' . now()->format('Y-m-d') . '.xlsx'
                        )),
                ]),

                Action::make('exportAllExcel')
                    ->label('استخراج الكل Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn ($livewire) => Excel::download(
                        new CollectedClientsExport($livewire->getFilteredTableQuery()),
                        'all-collected-clients-' . now()->format('Y-m-d') . '.xlsx'
                    )),
            ])
            ->striped()
            ->poll('30s');
    }
}
