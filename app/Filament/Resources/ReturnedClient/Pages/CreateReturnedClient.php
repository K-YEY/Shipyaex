<?php

namespace App\Filament\Resources\ReturnedClient\Pages;

use App\Filament\Resources\ReturnedClientResource;
use App\Services\ReturnedClientService;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateReturnedClient extends CreateRecord
{
    protected static string $resource = ReturnedClientResource::class;

    public function mount(): void
    {
        parent::mount();

        // Check if we have bulk selected orders from session
        $bulkOrders = session('bulk_return_client_orders');
        $bulkClientId = session('bulk_return_client_id');

        if ($bulkOrders && is_array($bulkOrders)) {
            // Pre-fill the form with the bulk selected data
            $this->form->fill([
                'client_id' => $bulkClientId,
                'selected_orders' => $bulkOrders,
                'return_date' => now()->format('Y-m-d'),
                'status' => 'pending',
            ]);

            // Clear the session data
            session()->forget(['bulk_return_client_orders', 'bulk_return_client_id']);
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

        // Update orders (link only, don't activate return flag)
        \App\Models\Order::whereIn('id', $orderIds)
            ->update([
                'returned_client_id' => $record->id,
            ]);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
