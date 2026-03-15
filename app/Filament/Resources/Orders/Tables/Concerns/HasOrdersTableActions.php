<?php

namespace App\Filament\Resources\Orders\Tables\Concerns;

use App\Models\Order;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use App\Exports\OrdersTemplateExport;
use App\Imports\OrdersImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

trait HasOrdersTableActions
{
    public static function getHeaderActions(): array
    {
        $isAdmin = self::$cachedUserIsAdmin;
        
        return [
            self::getBarcodeScannerAction(),
            
            Action::make('myOrders')
                ->label('My Orders')
                ->color('info')
                ->visible(fn() => $isAdmin || self::userCan('ViewMyOrdersAction:Order'))
                ->modalHeading('My Orders - Out for Delivery')
                ->modalWidth('7xl')
                ->modalContent(fn () => view('filament.orders.shipper-orders-table', [
                    'orders' => Order::where('shipper_id', auth()->id())->where('status', self::STATUS_OUT_FOR_DELIVERY)->get(),
                    'shipper' => auth()->user(),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn() => $isAdmin || self::userCan('ExportData:Order'))
                ->form([
                    TextInput::make('limit')->label('Number of Orders')->numeric()->placeholder('Leave empty for All'),
                ])
                ->action(function (array $data) {
                    $limit = !empty($data['limit']) ? (int) $data['limit'] : null;
                    return Excel::download(new OrdersExport(null, $limit), 'orders-' . now()->format('Y-m-d') . '.xlsx');
                }),

            ActionGroup::make([
                Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn() => $isAdmin || self::userCan('Create:Order'))
                    ->form([
                        FileUpload::make('file')->required()->disk('local')->directory('imports'),
                        Select::make('client_id')->relationship('client', 'name', fn($q) => $q->role('client'))->searchable(),
                        Select::make('shipper_id')->relationship('shipper', 'name', fn($q) => $q->role('shipper'))->searchable(),
                    ])
                    ->action(fn (array $data) => self::handleImport($data)),
                
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn() => $isAdmin || self::userCan('Create:Order'))
                    ->action(fn () => Excel::download(new OrdersTemplateExport(), 'template.xlsx')),
            ])->label('Actions')->icon('heroicon-m-ellipsis-vertical'),
        ];
    }

    protected static function getBarcodeScannerAction(): Action
    {
        return Action::make('barcodeScanner')
            ->label('Barcode Scanner')
            ->color('warning')
            ->modalHeading('Quick Barcode Scanner')
            ->modalWidth('2xl')
            ->visible(fn() => self::$cachedUserIsAdmin || self::userCan('BarcodeScannerAction:Order'))
            ->schema([
                TextInput::make('scanned_code')
                    ->label('Order Code')
                    ->placeholder('Scan or type...')
                    ->autofocus()
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn ($state, $set) => self::handleBarcodeScan($state, $set)),
                
                \Filament\Forms\Components\Placeholder::make('order_info')
                    ->content(fn ($get) => $get('order_data') ? view('filament.components.order-quick-info', ['order' => $get('order_data')]) : '🔍 Waiting...'),
                
                Hidden::make('order_id'),
                Hidden::make('order_data'),

                Select::make('action_type')
                    ->label('Action Required')
                    ->options([
                        'view' => 'View Details',
                        'mark_delivered' => 'Delivered',
                        'mark_undelivered' => 'Undelivered',
                        'mark_hold' => 'Hold',
                        'mark_out_for_delivery' => 'Out for Delivery',
                        'collect_shipper' => 'Collect Shipper',
                        'collect_client' => 'Collect Client',
                        'mark_return_shipper' => 'Activate Return',
                        'print_label' => 'Print Label',
                    ])
                    ->visible(fn ($get) => $get('order_id') !== null)
                    ->required(),
            ])
            ->action(fn (array $data) => self::handleBarcodeAction($data));
    }

    public static function getRecordActions(): array
    {
        $isAdmin = self::$cachedUserIsAdmin;
        
        return [
            ActionGroup::make([
                Action::make('copyOrder')
                    ->label('Copy Data')
                    ->icon('heroicon-o-clipboard-document')
                    ->action(fn () => Notification::make()->title('Copied!')->success()->send())
                    ->extraAttributes(fn ($record) => [
                        'onclick' => "const text = `Code: {$record->code}\nName: {$record->name}\nPhone: {$record->phone}\nAddress: {$record->address}`;navigator.clipboard.writeText(text);"
                    ]),

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn ($record) => "https://wa.me/+20" . ltrim($record->phone, '0'), true)
                    ->visible(fn ($record) => !empty($record->phone)),

                EditAction::make()->visible(fn($record) => !self::isFieldDisabled($record)),

                Action::make('printLabel')
                    ->label('Print Label')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn($record) => route('orders.print-label', $record->id), true)
                    ->visible(fn() => self::userCan('PrintLabelAction:Order')),
                
                Action::make('toggleCollectedShipper')
                    ->label(fn($record) => $record->collected_shipper ? '❌ Cancel Shipper' : '✅ Collect Shipper')
                    ->icon('heroicon-o-truck')
                    ->color(fn($record) => $record->collected_shipper ? 'danger' : 'success')
                    ->visible(fn() => self::userCan('ManageShipperCollectionAction:Order'))
                    ->requiresConfirmation()
                    ->action(fn($record) => self::handleToggleShipperCollection($record)),

                Action::make('toggleCollectedClient')
                    ->label(fn($record) => $record->collected_client ? '❌ Cancel Client' : '💰 Collect Client')
                    ->icon('heroicon-o-banknotes')
                    ->color(fn($record) => $record->collected_client ? 'danger' : 'primary')
                    ->visible(fn() => self::userCan('ManageCollections:Order'))
                    ->requiresConfirmation()
                    ->action(fn($record) => self::handleToggleClientCollection($record)),
                
                Action::make('toggleReturnShipper')
                    ->label(fn($record) => $record->return_shipper ? '❌ Cancel Return Shipper' : '↩️ Return Shipper')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color(fn($record) => $record->return_shipper ? 'danger' : 'info')
                    ->visible(fn() => self::userCan('ManageReturns:Order'))
                    ->requiresConfirmation()
                    ->action(fn($record) => self::handleToggleReturnShipper($record)),
            ])->icon('heroicon-m-ellipsis-vertical'),
        ];
    }

    public static function getBulkActions(): array
    {
        $isAdmin = self::$cachedUserIsAdmin;
        return [
            BulkActionGroup::make([
                BulkAction::make('bulkCollectShipper')
                    ->label('Bulk Collect Shipper')
                    ->visible(fn() => self::userCan('ManageShipperCollectionAction:Order'))
                    ->action(fn($records) => self::handleBulkCollectShipper($records)),
                
                BulkAction::make('bulkCollectClient')
                    ->label('Bulk Collect Client')
                    ->visible(fn() => self::userCan('ManageCollections:Order'))
                    ->action(fn($records) => self::handleBulkCollectClient($records)),

                DeleteBulkAction::make()->visible($isAdmin || self::userCan('DeleteAny:Order')),
            ])->label('Bulk Actions'),
        ];
    }

    // --- LOGIC HANDLERS ---

    protected static function handleBarcodeScan($state, $set)
    {
        if (!$state || strlen($state) < 2) return;
        
        $query = Order::with(['client', 'shipper', 'governorate', 'city']);
        $user = auth()->user();
        if ($user->isShipper()) {
            $query->where('shipper_id', $user->id)->where('collected_shipper', false);
        }

        $order = $query->where('code', $state)->orWhere('code', 'like', "%{$state}%")->first();
        
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
            ]);
        } else {
            $set('order_id', null);
            $set('order_data', null);
        }
    }

    protected static function handleBarcodeAction(array $data)
    {
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) return;
        
        $order = Order::find($orderId);
        if (!$order) return;

        switch ($data['action_type']) {
            case 'mark_delivered':
                $order->update(['status' => self::STATUS_DELIVERED, 'deliverd_at' => now()]);
                break;
            case 'mark_undelivered':
                $order->update(['status' => self::STATUS_UNDELIVERED]);
                break;
            case 'mark_hold':
                $order->update(['status' => self::STATUS_HOLD]);
                break;
            case 'mark_out_for_delivery':
                $order->update(['status' => self::STATUS_OUT_FOR_DELIVERY]);
                break;
            case 'collect_shipper':
                self::handleToggleShipperCollection($order);
                break;
            case 'collect_client':
                self::handleToggleClientCollection($order);
                break;
            case 'print_label':
                // Handled via notification
                Notification::make()->title('Opening Label...')->actions([
                    \Filament\Notifications\Actions\Action::make('open')->url(route('orders.print-label', $order->id), true)
                ])->send();
                break;
        }
        Notification::make()->title('Order updated')->success()->send();
    }

    protected static function handleToggleShipperCollection($record)
    {
        if ($record->collected_shipper) {
            $record->update(['collected_shipper' => false, 'collected_shipper_date' => null]);
            Notification::make()->title('Shipper Collection Cancelled')->success()->send();
            return;
        }
        
        if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
            Notification::make()->title('Error')->body('Order must be Delivered or Undelivered')->warning()->send();
            return;
        }

        $record->update(['collected_shipper' => true, 'collected_shipper_date' => now()]);
        Notification::make()->title('Collected from Shipper')->success()->send();
    }

    protected static function handleToggleClientCollection($record)
    {
        if ($record->collected_client) {
            $record->update(['collected_client' => false, 'collected_client_date' => null]);
            Notification::make()->title('Client Collection Cancelled')->success()->send();
            return;
        }

        if ($record->status !== self::STATUS_DELIVERED && $record->status !== self::STATUS_UNDELIVERED) {
            Notification::make()->title('Error')->body('Order must be Delivered or Undelivered')->warning()->send();
            return;
        }

        if (self::requireShipperFirst() && !$record->collected_shipper) {
            Notification::make()->title('Error')->body('Must collect from shipper first')->danger()->send();
            return;
        }
        $record->update(['collected_client' => true, 'collected_client_date' => now()]);
        Notification::make()->title('Settled for Client')->success()->send();
    }

    protected static function handleToggleReturnShipper($record)
    {
        $newValue = !$record->return_shipper;
        $record->update(['return_shipper' => $newValue, 'return_shipper_date' => $newValue ? now() : null]);
        Notification::make()->title($newValue ? 'Return Activated' : 'Return Cancelled')->success()->send();
    }

    protected static function handleImport(array $data)
    {
        $file = is_array($data['file']) ? reset($data['file']) : $data['file'];
        
        $import = new OrdersImport($data['client_id'] ?? null, $data['shipper_id'] ?? null);
        
        try {
            Excel::import($import, $file, 'local');
            Notification::make()->title('Import Successful')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Import Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
