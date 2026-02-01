<?php

namespace App\Filament\Resources\CollectedClients\Pages;

use App\Filament\Resources\CollectedClients\CollectedClientResource;
use App\Services\CollectedClientService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCollectedClient extends CreateRecord
{
    protected static string $resource = CollectedClientResource::class;

    protected static ?string $title = 'إنشاء تحصيل عميل';

    protected static ?string $breadcrumb = 'Create';

    public function mount(): void
    {
        parent::mount();

        // Check if we have bulk selected orders from session
        $bulkOrders = session('bulk_collect_client_orders');
        $bulkClientId = session('bulk_collect_client_id');

        if ($bulkOrders && is_array($bulkOrders)) {
            // Pre-fill the form with the bulk selected data
            $this->form->fill([
                'client_id' => $bulkClientId,
                'selected_orders' => $bulkOrders,
                'collection_date' => now()->format('Y-m-d'),
            ]);

            // Clear the session data
            session()->forget(['bulk_collect_client_orders', 'bulk_collect_client_id']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // If user is client, use their ID
        if ($user->isClient() && !$user->isAdmin()) {
            $data['client_id'] = $user->id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $service = new CollectedClientService();

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
        $errors = $service->validateOrdersForCollection($orderIds, $data['client_id']);
        
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
            $data['client_id'],
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
