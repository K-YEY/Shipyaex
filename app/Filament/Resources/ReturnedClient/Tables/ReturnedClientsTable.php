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
                    ->sortable(),
                
                TextColumn::make('client.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable()
                    ->visible($canManage),
                
                TextColumn::make('return_date')
                    ->label('تاريخ الارتجاع')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('number_of_orders')
                    ->label('عدد المرتجعات')
                    ->badge(),
                
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => \App\Enums\CollectingStatus::tryFrom($state)?->color() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('client_id')
                    ->label('العميل')
                    ->relationship('client', 'name', fn ($query) => $query->role('client'))
                    ->visible($canManage),
                
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(\App\Enums\CollectingStatus::class),
            ])
            ->actions([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل')
                    ->visible(fn ($record) => $record->status === 'pending'),
                
                Action::make('approve')
                    ->label('اعتماد التسليم للعميل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending' && $canManage)
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
                    ->visible(fn ($record) => $record->status === 'completed')
                    ->url(fn ($record) => route('returns.client.invoice', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
