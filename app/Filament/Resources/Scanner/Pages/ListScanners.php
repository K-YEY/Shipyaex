<?php

namespace App\Filament\Resources\Scanner\Pages;

use App\Filament\Resources\Scanner\ScannerResource;
use App\Models\Order;
use App\Models\User;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\View\View;

class ListScanners extends ListRecords
{
    protected static string $resource = ScannerResource::class;

    public array $scannedOrders = [];
    public bool $autoProcess = true;
    public string $selectedAction = 'view';
    public ?int $targetShipperId = null;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.scanner.header', [
            'scannedOrders' => $this->scannedOrders,
            'autoProcess' => $this->autoProcess,
            'selectedAction' => $this->selectedAction,
            'targetShipperId' => $this->targetShipperId,
        ]);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // We only want to show orders that were scanned in this session
        $ids = collect($this->scannedOrders)->pluck('id')->toArray();
        
        if (empty($ids)) {
            return Order::query()->whereRaw('1 = 0');
        }

        return Order::query()->whereIn('id', $ids);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->badge()
                    ->color('info'),
                TextColumn::make('name')
                    ->label('الاسم'),
                TextColumn::make('phone')
                    ->label('الهاتف'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'deliverd' => 'success',
                        'out for delivery' => 'info',
                        'hold' => 'warning',
                        'undelivered' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('EGP'),
                TextColumn::make('shipper.name')
                    ->label('المندوب'),
            ])
            ->actions([
                TableAction::make('remove')
                    ->label('إزالة')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn ($record) => $this->removeOrder($record->id)),
            ])
            ->emptyStateHeading('لا توجد طلبات ممسوحة')
            ->emptyStateDescription('ابدأ بمسح الباركود لإضافة الطلبات للقائمة')
            ->emptyStateIcon('heroicon-o-qr-code');
    }

    public function processScannedCode(string $code): void
    {
        $code = trim($code);
        if (empty($code)) return;

        $order = Order::where('code', $code)
            ->orWhere('external_code', $code)
            ->first();

        if (!$order) {
            Notification::make()->title('❌ الأوردر غير موجود')->danger()->send();
            return;
        }

        if (collect($this->scannedOrders)->contains('id', $order->id)) {
            Notification::make()->title('⚠️ الأوردر مضاف بالفعل')->warning()->send();
            return;
        }

        $this->scannedOrders[] = ['id' => $order->id];
        
        Notification::make()->title("✅ تم إضافة أوردر #{$order->code}")->success()->send();

        if ($this->autoProcess && $this->selectedAction !== 'view') {
            $this->quickAction($order->id, $this->selectedAction);
        }
    }

    public function removeOrder(int $orderId): void
    {
        $this->scannedOrders = array_values(
            array_filter($this->scannedOrders, fn($o) => $o['id'] !== $orderId)
        );
    }

    public function clearAll(): void
    {
        $this->scannedOrders = [];
        Notification::make()->title('تم مسح القائمة')->success()->send();
    }

    public function quickAction(int $orderId, string $action): void
    {
        $order = Order::find($orderId);
        if (!$order) return;

        $user = auth()->user();

        switch ($action) {
            case 'delivered':
                if ($user->can('ChangeStatusAction:Order')) {
                    $order->update(['status' => 'deliverd', 'deliverd_at' => now()]);
                }
                break;
            case 'assign_shipper':
                if ($user->can('AssignShipper:Order') && $this->targetShipperId) {
                    $order->update([
                        'shipper_id' => $this->targetShipperId,
                        'status' => 'out for delivery'
                    ]);
                }
                break;
            // ... other actions can be added here
        }
    }

    public function getShippers(): array
    {
        return User::role('shipper')->pluck('name', 'id')->toArray();
    }
}
