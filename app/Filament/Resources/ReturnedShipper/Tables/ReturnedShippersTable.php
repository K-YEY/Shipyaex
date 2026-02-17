<?php

namespace App\Filament\Resources\ReturnedShipper\Tables;

use App\Models\ReturnedShipper;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Services\ReturnedShipperService;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturnedShippersExport;
use Illuminate\Database\Eloquent\Collection;

class ReturnedShippersTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        // Use permission check instead of role
        $canManage = $user->can('update_returned_shipper');

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم الكشف')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:ReturnedShipper'))
                    ->sortable(),
                
                TextColumn::make('shipper.name')
                    ->label('المندوب')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:ReturnedShipper'))
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('return_date')
                    ->label('تاريخ الارتجاع')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewReturnDateColumn:ReturnedShipper'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('number_of_orders')
                    ->label('عدد الطلبات')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountColumn:ReturnedShipper'))
                    ->badge(),
                
                TextColumn::make('status')
                    ->label('الحالة')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:ReturnedShipper'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->color() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('shipper_id')
                    ->label('المندوب')
                    ->relationship('shipper', 'name', fn ($query) => $query->role('shipper'))
                    ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ViewShipperColumn:ReturnedShipper')),
                
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),
            ])
            ->actions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Update:ReturnedShipper') && $record->status === 'pending'),
                
                Action::make('approve')
                    ->label('اعتماد الاستلام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Approve:ReturnedShipper') && $record->status === 'pending')
                    ->action(function ($record) {
                        $service = new ReturnedShipperService();
                        $service->approveReturn($record);
                        
                        Notification::make()
                            ->title('تم اعتماد استلام المرتجعات ✅')
                            ->success()
                            ->send();
                    }),

                Action::make('printInvoice')
                    ->label('طباعة الفاتورة')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('PrintInvoice:ReturnedShipper') && $record->status === 'completed')
                    ->url(fn ($record) => route('returns.shipper.invoice', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportExcel')
                        ->label('استخراج Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(fn (Collection $records) => Excel::download(
                            new ReturnedShippersExport(null, $records->pluck('id')->toArray()),
                            'returned-shippers-' . now()->format('Y-m-d') . '.xlsx'
                        )),
                ]),

                Action::make('exportAllExcel')
                    ->label('استخراج الكل Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn ($livewire) => Excel::download(
                        new ReturnedShippersExport($livewire->getFilteredTableQuery()),
                        'all-returned-shippers-' . now()->format('Y-m-d') . '.xlsx'
                    )),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
