<?php

namespace App\Services;

use App\Models\ReturnedClient;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnedClientService
{
    /**
     * Calculate order statistics
     */
    public function calculateStats(array $orderIds): array
    {
        return [
            'number_of_orders' => count($orderIds),
        ];
    }

    /**
     * Update existing return
     */
    public function updateReturn(ReturnedClient $record, array $orderIds): ReturnedClient
    {
        return DB::transaction(function () use ($record, $orderIds) {
            // Remove old orders from this record and reset return flag
            Order::where('returned_client_id', $record->id)
                ->update([
                    'returned_client_id' => null,
                    'return_client' => false,
                    'return_client_date' => null,
                ]);

            // Link new orders (without activating return flag)
            Order::whereIn('id', $orderIds)
                ->update([
                    'returned_client_id' => $record->id,
                ]);

            $record->update([
                'number_of_orders' => count($orderIds),
            ]);

            return $record->fresh();
        });
    }

    /**
     * Approve return
     */
    public function approveReturn(ReturnedClient $record): void
    {
        DB::transaction(function () use ($record) {
            // Activate return flag for all linked orders on approval only
            Order::where('returned_client_id', $record->id)
                ->update([
                    'return_client' => true,
                    'return_client_date' => now(),
                ]);

            $record->update([
                'status' => 'completed',
            ]);
        });
    }
}
