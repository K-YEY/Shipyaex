<?php

namespace App\Filament\Resources\ReturnedClient\Tables;

use App\Models\ReturnedClient;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Services\ReturnedClientService;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturnedClientsExport;
use Illuminate\Database\Eloquent\Collection;

class ReturnedClientsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        // Use permission check instead of role
        $canManage = $user->can('update_returned_client');

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم الكشف')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewIdColumn:ReturnedClient'))
                    ->sortable(),
                
                TextColumn::make('client.name')
                    ->label('العميل')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewClientColumn:ReturnedClient'))
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('return_date')
                    ->label('تاريخ الارتجاع')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewReturnDateColumn:ReturnedClient'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('number_of_orders')
                    ->label('عدد المرتجعات')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewOrdersCountColumn:ReturnedClient'))
                    ->badge(),
                
                TextColumn::make('status')
                    ->label('الحالة')
                    ->visible(fn () => auth()->user()->isAdmin() || auth()->user()->can('ViewStatusColumn:ReturnedClient'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->color() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('client_id')
                    ->label('العميل')
                    ->relationship('client', 'name', fn ($query) => $query->role('client'))
                    ->visible(fn() => auth()->user()->isAdmin() || auth()->user()->can('ViewClientColumn:ReturnedClient')),
                
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),
            ])
            ->actions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Update:ReturnedClient') && $record->status === 'pending'),
                
                Action::make('approve')
                    ->label('اعتماد التسليم للعميل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('Approve:ReturnedClient') && $record->status === 'pending')
                    ->action(function ($record) {
                        $service = new ReturnedClientService();
                        $service->approveReturn($record);
                        
                        Notification::make()
                            ->title('تم اعتماد تسليم المرتجعات للعميل ✅')
                            ->success()
                            ->send();
                    }),

                Action::make('printInvoice')
                    ->label('طباعة الفاتورة')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn ($record) => auth()->user()->isAdmin() || auth()->user()->can('PrintInvoice:ReturnedClient') && $record->status === 'completed')
                    ->url(fn ($record) => route('returns.client.invoice', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportExcel')
                        ->label('استخراج Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(fn (Collection $records) => Excel::download(
                            new ReturnedClientsExport(null, $records->pluck('id')->toArray()),
                            'returned-clients-' . now()->format('Y-m-d') . '.xlsx'
                        )),
                ]),

                Action::make('exportAllExcel')
                    ->label('استخراج الكل Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn ($livewire) => Excel::download(
                        new ReturnedClientsExport($livewire->getFilteredTableQuery()),
                        'all-returned-clients-' . now()->format('Y-m-d') . '.xlsx'
                    )),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
