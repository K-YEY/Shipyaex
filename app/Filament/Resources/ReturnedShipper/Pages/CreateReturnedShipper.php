<?php

namespace App\Filament\Resources\ReturnedShipper\Pages;

use App\Filament\Resources\ReturnedShipperResource;
use App\Services\ReturnedShipperService;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateReturnedShipper extends CreateRecord
{
    protected static string $resource = ReturnedShipperResource::class;

    public function mount(): void
    {
        parent::mount();

        // Check if we have bulk selected orders from session
        $bulkOrders = session('bulk_return_shipper_orders');
        $bulkShipperId = session('bulk_return_shipper_id');

        if ($bulkOrders && is_array($bulkOrders)) {
            // Pre-fill the form with the bulk selected data
            $this->form->fill([
                'shipper_id' => $bulkShipperId,
                'selected_orders' => $bulkOrders,
                'return_date' => now()->format('Y-m-d'),
                'status' => 'pending',
            ]);

            // Clear the session data
            session()->forget(['bulk_return_shipper_orders', 'bulk_return_shipper_id']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number_of_orders'] = count($data['selected_orders'] ?? []);
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $orderIds = $this->data['selected_orders'] ?? [];

        // ربط الأوردرات (بدون تفعيل علامة المرتجع)
        \App\Models\Order::whereIn('id', $orderIds)
            ->update([
                'returned_shipper_id' => $record->id,
            ]);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
