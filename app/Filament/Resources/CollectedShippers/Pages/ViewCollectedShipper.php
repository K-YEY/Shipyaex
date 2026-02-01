<?php

namespace App\Filament\Resources\CollectedShippers\Pages;

use App\Filament\Resources\CollectedShippers\CollectedShipperResource;
use App\Services\CollectedShipperService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewCollectedShipper extends ViewRecord
{
    protected static string $resource = CollectedShipperResource::class;

    protected static ?string $title = 'تفاصيل التحصيل';

    protected static ?string $breadcrumb = 'عرض';

    protected function getHeaderActions(): array
    {
        $isAdmin = auth()->user()->isAdmin();

        return [
            EditAction::make()
                ->label('Edit')
                ->visible(fn () => $this->record->status === 'pending'),

            Action::make('approve')
                ->label('اعتماد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $isAdmin && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $service = new CollectedShipperService();
                    $service->approveCollection($this->record);

                    Notification::make()
                        ->title('تم Approve collection بنجاح ✅')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $isAdmin && $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $service = new CollectedShipperService();
                    $service->cancelCollection($this->record);

                    Notification::make()
                        ->title('Collection Cancelled ❌')
                        ->danger()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Action::make('print')
                ->label('Print الفاتورة')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn () => $this->record->status === 'completed')
                ->url(fn () => route('collecting.shipper.invoice', $this->record->id))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات التحصيل')
                    ->icon('heroicon-o-banknotes')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('id')
                            ->label('رقم التحصيل')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('shipper.name')
                            ->label('Shipper')
                            ->icon('heroicon-o-truck')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('collection_date')
                            ->label('تاريخ التحصيل')
                            ->date('Y-m-d')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'pending' => 'قيد اNoنتظار',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                default => $state,
                            })
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ]),

                Section::make('ملخص المبالغ')
                    ->icon('heroicon-o-calculator')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('number_of_orders')
                            ->label('عدد Orderات')
                            ->badge()
                            ->color('info')
                            ->suffix(' طلب'),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('EGP')
                            ->color('primary'),

                        TextEntry::make('shipper_fees')
                            ->label('رسوم Shipper')
                            ->money('EGP')
                            ->color('warning'),

                        TextEntry::make('net_amount')
                            ->label('الصافي')
                            ->money('EGP')
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                    ]),

                Section::make('Orderات المرتبطة')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->label('')
                            ->schema([
                                TextEntry::make('code')
                                    ->label('كود Order')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('name')
                                    ->label('اسم Client'),

                                TextEntry::make('phone')
                                    ->label('Phone'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'deliverd' => 'Delivered',
                                        'undelivered' => 'لم يDelivered',
                                        default => $state,
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'deliverd' => 'success',
                                        'undelivered' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('has_return')
                                    ->label('مرتجع')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                                    ->color(fn ($state) => $state ? 'warning' : 'gray'),

                                TextEntry::make('cod')
                                    ->label('COD')
                                    ->money('EGP'),

                                TextEntry::make('shipper_fees')
                                    ->label('رسوم Shipper')
                                    ->money('EGP'),
                            ])
                            ->columns(7),
                    ]),

                Section::make('معلومات إضافية')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('مNoحظات')
                            ->placeholder('No توجد مNoحظات')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('Y-m-d H:i'),
                    ]),
            ]);
    }
}
