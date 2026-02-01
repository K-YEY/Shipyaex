<?php

namespace App\Filament\Resources\CollectedShippers\Pages;

use App\Filament\Resources\CollectedShippers\CollectedShipperResource;
use App\Models\Order;
use App\Services\CollectedShipperService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCollectedShipper extends CreateRecord
{
    protected static string $resource = CollectedShipperResource::class;

    protected static ?string $title = 'إنشاء تحصيل Shipper';

    protected static ?string $breadcrumb = 'Create';

    public function mount(): void
    {
        parent::mount();

        // Check if we have bulk selected orders from session
        $bulkOrders = session('bulk_collect_shipper_orders');
        $bulkShipperId = session('bulk_collect_shipper_id');

        if ($bulkOrders && is_array($bulkOrders)) {
            // Pre-fill the form with the bulk selected data
            $this->form->fill([
                'shipper_id' => $bulkShipperId,
                'selected_orders' => $bulkOrders,
                'collection_date' => now()->format('Y-m-d'),
            ]);

            // Clear the session data
            session()->forget(['bulk_collect_shipper_orders', 'bulk_collect_shipper_id']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // If user is shipper, use their ID
        if ($user->isShipper() && !$user->isAdmin()) {
            $data['shipper_id'] = $user->id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $service = new CollectedShipperService();

        // التحقق من وجود طلبات
        $orderIds = $data['selected_orders'] ?? [];
        
        if (empty($orderIds)) {
            Notification::make()
                ->title('خطأ')
                ->body('يجب اختيار طلب واحد على الأقل للتحصيل')
                ->danger()
                ->send();
            
            $this->halt();
        }

        // التحقق من صNoحية Orderات
        $errors = $service->validateOrdersForCollection($orderIds, $data['shipper_id']);
        
        if (!empty($errors)) {
            Notification::make()
                ->title('أخطاء في التحقق')
                ->body(implode("\n", $errors))
                ->danger()
                ->send();
            
            $this->halt();
        }

        // إنشاء التحصيل
        return $service->createCollection(
            $data['shipper_id'],
            $orderIds,
            $data['collection_date']
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء التحصيل بنجاح ✅';
    }
}
