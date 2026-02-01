<?php

namespace App\Services;

use App\Models\ReturnedShipper;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnedShipperService
{
    /**
     * حساب عدد Orderات
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
    public function updateReturn(ReturnedShipper $record, array $orderIds): ReturnedShipper
    {
        return DB::transaction(function () use ($record, $orderIds) {
            // إزالة Orderات القديمة من هذا السجل وتصفير عNoمة المرتجع
            Order::where('returned_shipper_id', $record->id)
                ->update([
                    'returned_shipper_id' => null,
                    'return_shipper' => false,
                    'return_shipper_date' => null,
                ]);

            // ربط Orderات الجديدة (بدون تفعيل علامة المرتجع)
            Order::whereIn('id', $orderIds)
                ->update([
                    'returned_shipper_id' => $record->id,
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
    public function approveReturn(ReturnedShipper $record): void
    {
        DB::transaction(function () use ($record) {
            // تفعيل علامة المرتجع لكافة الأوردرات المرتبطة عند الاعتماد فقط
            Order::where('returned_shipper_id', $record->id)
                ->update([
                    'return_shipper' => true,
                    'return_shipper_date' => now(),
                ]);

            $record->update([
                'status' => 'completed',
            ]);
        });
    }
}
