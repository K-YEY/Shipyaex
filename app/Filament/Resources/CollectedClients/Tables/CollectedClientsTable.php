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
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CollectedClientsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        // Use permission check instead of role
        $canManage = $user->can('update_collected_client');

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('bold'),

                TextColumn::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->date('Y-m-d')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('number_of_orders')
                    ->label('عدد الطلبات')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->state(fn ($record) => number_format($record->total_amount, 2) . ' ج.م')
                    ->sortable()
                    ->alignEnd()
                    ->color('primary'),

                TextColumn::make('fees')
                    ->label('مصاريف الشركة')
                    ->state(fn ($record) => number_format($record->fees, 2) . ' ج.م')
                    ->sortable()
                    ->alignEnd()
                    ->color('warning')
                    ->visible($canManage),

                TextColumn::make('net_amount')
                    ->label('الصافي للعميل')
                    ->state(fn ($record) => number_format($record->net_amount, 2) . ' ج.م')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),

                SelectFilter::make('client_id')
                    ->label('العميل')
                    ->options(function () use ($canManage) {
                        if (!$canManage) {
                            return [];
                        }
                        
                        // جلب Clients اللي عندهم أوردرات محصلة فقط
                        return \App\Models\User::role('client')
                            ->whereHas('clientOrders', function ($query) {
                                $query->where('collected_client', true);
                            })
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->visible($canManage),

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
                        ->visible(fn ($record) => $record->status === 'pending'),

                    // عرض الطلبات
                    Action::make('viewOrders')
                        ->label('عرض الطلبات')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
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
                        ->visible(fn ($record) => $canManage && $record->status === 'pending')
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
                        ->visible(fn ($record) => $canManage && $record->status === 'pending')
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
                        ->visible(fn ($record) => $record->status === 'completed')
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
                        ->visible($canManage)
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
                        ->visible($canManage),
                ]),
            ])
            ->striped()
            ->poll('30s');
    }
}
