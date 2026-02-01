<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
    
    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    // Scanner Mode Properties
    public bool $scannerMode = false;
    public array $scannedOrders = [];
    public bool $autoProcess = true;
    public string $selectedAction = 'view';

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        // ✅ تحقق فقط لو الUser عميل
        if ($user->isClient()) {
            $start = Setting::get('working_hours_orders_start', '05:00');
            $end   = Setting::get('working_hours_orders_end', '17:00');

            $now = Carbon::now()->format('H:i');

            // ⏰ لو خارج الوقت المسموح به
            if (!($now >= $start && $now <= $end)) {
                Notification::make()
                    ->title('إضافة أوردر!')
                    ->body("مش مسموح بإضافة أوردرات دلوقتي يا ريس (المواعيد من {$start} لحد {$end} بس).")
                    ->danger()
                    ->persistent()
                    ->send();

            }
        }
        if(!$user->isShipper()){
            return [
                $this->getScannerToggleAction(),
                CreateAction::make()
                ->label('إضافة أوردر جديد')
                ->icon('heroicon-o-plus')
            ->visible(!$this->scannerMode),
        ];
        }
        return [];
    }

    protected function getScannerToggleAction(): Action
    {
        return Action::make('toggleScanner')
            ->label($this->scannerMode ? 'رجوع للجدول' : 'سكانر الباركود (Barcode)')
            ->icon($this->scannerMode ? 'heroicon-o-table-cells' : 'heroicon-o-qr-code')
            ->color($this->scannerMode ? 'gray' : 'info')
            ->action(function () {
                $this->scannerMode = !$this->scannerMode;
                if (!$this->scannerMode) {
                    // Reset scanned orders when exiting scanner mode
                    $this->scannedOrders = [];
                }
            });
    }

    /**
     * عرض الـ Scanner Mode كـ Empty State للجدول
     */
    protected function getTableEmptyState(): ?View
    {
        if (!$this->scannerMode) {
            return null;
        }

        return view('filament.orders.scanner-mode-content', [
            'scannedOrders' => $this->scannedOrders,
            'autoProcess' => $this->autoProcess,
            'selectedAction' => $this->selectedAction,
        ]);
    }

    /**
     * عند تفعيل الـ scanner mode، نرجع query فارغ لإخفاء الجدول
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->scannerMode) {
            return Order::query()->whereRaw('1 = 0'); // Empty result
        }

        // إرجاع الـ query اNoفتراضي من الـ Resource
        return static::getResource()::getEloquentQuery();
    }

    public function processScannedCode(string $code): void
    {
        $code = trim($code);
        
        if (empty($code)) {
            return;
        }

        // Search عن الأوردر
        $order = Order::where('code', $code)
            ->orWhere('code', 'like', "%{$code}%")
            ->orWhere('external_code', 'like', "%{$code}%")
            ->with(['client', 'shipper', 'governorate', 'city'])
            ->first();

        if (!$order) {
            Notification::make()
                ->title('❌ الأوردر مش موجود')
                ->body("الكود: {$code}")
                ->danger()
                ->send();
            return;
        }

        // التحقق من عدم وجود الأوردر مسبقاً في القائمة
        $exists = collect($this->scannedOrders)->contains('id', $order->id);
        
        if ($exists) {
            Notification::make()
                ->title('⚠️ الأوردر موجود أصلاً')
                ->body("أوردر رقم #{$order->code} موجود في القائمة فعلاً")
                ->warning()
                ->send();
            return;
        }

        // Add الأوردر للقائمة
        $this->scannedOrders[] = [
            'id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'name' => $order->name,
            'phone' => $order->phone,
            'address' => $order->address,
            'governorate' => $order->governorate?->name ?? '-',
            'city' => $order->city?->name ?? '-',
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'fees' => $order->fees,
            'cod' => $order->cod,
            'client' => $order->client?->name ?? '-',
            'shipper' => $order->shipper?->name ?? '-',
            'collected_shipper' => $order->collected_shipper,
            'collected_client' => $order->collected_client,
            'has_return' => $order->has_return,
            'created_at' => $order->created_at?->format('Y-m-d'),
        ];

        Notification::make()
            ->title("✅ ضفت أوردر رقم #{$order->code}")
            ->body("المستلم: {$order->name} - المبلغ: {$order->total_amount} ج.م")
            ->success()
            ->send();

        // Auto-process if enabled
        if ($this->autoProcess && $this->selectedAction !== 'view') {
            $this->quickAction($order->id, $this->selectedAction);
        }
    }

    public function removeOrder(int $orderId): void
    {
        $this->scannedOrders = array_values(
            array_filter($this->scannedOrders, fn($order) => $order['id'] !== $orderId)
        );

        Notification::make()
            ->title('تم حذف الأوردر من القائمة')
            ->success()
            ->send();
    }

    public function clearScannedOrders(): void
    {
        $this->scannedOrders = [];
        Notification::make()
            ->title('تم مسح كل الأوردرات من القائمة')
            ->success()
            ->send();
    }

    public function quickAction(int $orderId, string $action): void
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            Notification::make()
                ->title('❌ Order Not Found')
                ->danger()
                ->send();
            return;
        }

        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isShipper = $user->isShipper();
        $isClient = $user->isClient();

        switch ($action) {
            case 'delivered':
                if (!$isAdmin && !$isShipper) {
                    Notification::make()
                        ->title('❌ الحركة دي مش مسموحة ليك')
                        ->danger()
                        ->send();
                    return;
                }
                
                $order->update([
                    'status' => 'deliverd',
                    'deliverd_at' => now(),
                ]);
                
                $this->updateOrderInList($orderId, ['status' => 'deliverd']);
                
                Notification::make()
                    ->title("✅ أوردر رقم #{$order->code} اتسلم بنجاح")
                    ->success()
                    ->send();
                break;

            case 'collected_shipper':
                if (!$isAdmin && !$isShipper) {
                    Notification::make()
                        ->title('❌ Action Not Allowed')
                        ->danger()
                        ->send();
                    return;
                }
                
                $order->update([
                    'collected_shipper' => true,
                    'collected_shipper_date' => now(),
                ]);
                
                $this->updateOrderInList($orderId, ['collected_shipper' => true]);
                
                Notification::make()
                    ->title("📦 الكابتن سلم فلوس أوردر رقم #{$order->code}")
                    ->success()
                    ->send();
                break;

            case 'collected_client':
                if (!$isAdmin && !$isClient) {
                    Notification::make()
                        ->title('❌ Action Not Allowed')
                        ->danger()
                        ->send();
                    return;
                }
                
                // ✅ التحقق من إعداد ترتيب التحصيل
                $requireShipperFirst = Setting::get('require_shipper_collection_first', 'yes') === 'yes';
                
                if ($requireShipperFirst && !$order->collected_shipper) {
                    Notification::make()
                        ->title('❌ مش ينفع نسوي مع العميل')
                        ->body('لازم نحصل من الكابتن الأول يا ريس')
                        ->danger()
                        ->send();
                    return;
                }
                
                $order->update([
                    'collected_client' => true,
                    'collected_client_date' => now(),
                ]);
                
                $this->updateOrderInList($orderId, ['collected_client' => true]);
                
                Notification::make()
                    ->title("💰 تم عمل تسوية للعميل للأوردر رقم #{$order->code}")
                    ->success()
                    ->send();
                break;

            case 'return_shipper':
                if (!$isAdmin && !$isShipper) {
                    Notification::make()
                        ->title('❌ Action Not Allowed')
                        ->danger()
                        ->send();
                    return;
                }
                
                $order->update([
                    'return_shipper' => true,
                    'return_shipper_date' => now(),
                ]);
                
                $this->updateOrderInList($orderId, ['return_shipper' => true]);
                
                Notification::make()
                    ->title("↩️ تم تفعيل مرتجع الكابتن للأوردر رقم #{$order->code}")
                    ->success()
                    ->send();
                break;
        }
    }

    protected function updateOrderInList(int $orderId, array $updates): void
    {
        $this->scannedOrders = array_map(function ($order) use ($orderId, $updates) {
            if ($order['id'] === $orderId) {
                return array_merge($order, $updates);
            }
            return $order;
        }, $this->scannedOrders);
    }

    public function processAllOrders(): void
    {
        if (empty($this->scannedOrders)) {
            return;
        }

        $count = 0;
        foreach ($this->scannedOrders as $orderData) {
            if ($this->selectedAction !== 'view') {
                $this->quickAction($orderData['id'], $this->selectedAction);
                $count++;
            }
        }

        Notification::make()
            ->title("تمت معالجة {$count} أوردر بنجاح")
            ->success()
            ->send();
    }

    public function getTotals(): array
    {
        return [
            'count' => count($this->scannedOrders),
            'total_amount' => array_sum(array_column($this->scannedOrders, 'total_amount')),
            'fees' => array_sum(array_column($this->scannedOrders, 'fees')),
            'cod' => array_sum(array_column($this->scannedOrders, 'cod')),
        ];
    }

    public function getActionOptions(): array
    {
        $user = auth()->user();
        $options = [
            'view' => '👁️ عرض فقط (بدون إجراء)',
        ];

        if (!$user->isClient()) {
            $options['delivered'] = '✅ تسليم الأوردر';
            $options['collected_shipper'] = '📦 تحصيل من الكابتن';
            $options['return_shipper'] = '↩️ مرتجع من الكابتن';
        }

        if (!$user->isShipper()) {
            $options['collected_client'] = '💰 تسوية مع العميل';
        }

        return $options;
    }
}
