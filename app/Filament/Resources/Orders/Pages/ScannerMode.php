<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;

class ScannerMode extends Page
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.orders.pages.scanner-mode';

    protected static ?string $title = 'ماسح الباركود (Barcode)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    public array $scannedOrders = [];
    public bool $autoProcess = true;
    public string $selectedAction = 'view';
    public ?int $targetShipperId = null;

    public function getHeaderActions(): array
    {
        return [];
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

        switch ($action) {
            case 'delivered':
                if (!$user->can('ChangeStatusAction:Order')) {
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
                if (!$user->can('ManageShipperCollectionAction:Order')) {
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
                if (!$user->can('ManageClientCollectionAction:Order')) {
                    Notification::make()
                        ->title('❌ Action Not Allowed')
                        ->danger()
                        ->send();
                    return;
                }
                
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
                if (!$user->can('ManageShipperReturnAction:Order')) {
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

            case 'assign_shipper':
                if (!$user->can('AssignShipper:Order')) {
                    Notification::make()
                        ->title('❌ Action Not Allowed')
                        ->danger()
                        ->send();
                    return;
                }

                if (!$this->targetShipperId) {
                    Notification::make()
                        ->title('⚠️ عفواً')
                        ->body('لازم تختار مندوب الأول يا ريس')
                        ->warning()
                        ->send();
                    return;
                }

                $shipper = User::find($this->targetShipperId);
                
                $order->update([
                    'shipper_id' => $this->targetShipperId,
                    'status' => 'out for delivery',
                ]);
                
                $this->updateOrderInList($orderId, [
                    'shipper' => $shipper?->name ?? 'غير معروف',
                    'status' => 'out for delivery'
                ]);
                
                Notification::make()
                    ->title("🚚 تم إسناد أوردر #{$order->code} للمندوب {$shipper?->name}")
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

        if ($user->can('ChangeStatusAction:Order')) {
            $options['delivered'] = '✅ تسليم الأوردر';
        }

        if ($user->can('ManageShipperCollectionAction:Order')) {
            $options['collected_shipper'] = '📦 تحصيل من الكابتن';
        }
        
        if ($user->can('ManageClientCollectionAction:Order')) {
            $options['collected_client'] = '💰 تسوية مع العميل';
        }

        if ($user->can('ManageShipperReturnAction:Order')) {
            $options['return_shipper'] = '↩️ مرتجع من الكابتن';
        }

        if ($user->can('AssignShipper:Order')) {
            $options['assign_shipper'] = '🚚 إسناد / تحويل لمندوب';
        }

        return $options;
    }

    public function getShippers(): array
    {
        return User::role('shipper')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
