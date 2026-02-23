<?php

namespace App\Services;

use App\Enums\CollectingStatus;
use App\Models\CollectedClient;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CollectedClientService
{
    /**
     * الحصول على Orderات المتاحة للCollect for Client
     * Apply double verification binding conditions
     */
    public function getAvailableOrdersForClient(int $clientId): Collection
    {
        return Order::query()
            ->where('client_id', $clientId)
            ->availableForClientCollecting()
            ->get();
    }

    /**
     * التحقق من صحة Orderات قبل الإنشاء
     */
    public function validateOrdersForCollection(array $orderIds, int $clientId): array
    {
        $errors = [];
        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            // التحقق من أن Order تابع للعميل
            if ($order->client_id !== $clientId) {
                $errors[] = "Order #{$order->code} does not belong to this client";
                continue;
            }

            // التحقق من أهلية Order بناءً على القواعد الجديدة
            if (!$this->isOrderEligibleForCollection($order)) {
                $errors[] = "Order #{$order->code} does not meet collection requirements (تحتاج اعتماد تحصيل/مرتجع Shippers أوNoً)";
            }
        }

        return $errors;
    }

    /**
     * التحقق من أهلية Order للCollect for Client
     */
    public function isOrderEligibleForCollection(Order $order): bool
    {
        // 1. تعريف المتغيرات (Flags & Status)
        $status = $order->status;
        // معاملة has_return = null كأنها false
        $has_return = (bool) $order->has_return;

        $requireShipperFirst = \App\Models\Setting::get('require_shipper_collection_first', 'yes') === 'yes';

        // التحقق من اعتماد تحصيل Shipper ومرتجع Shipper
        $collect_shipper = $order->collectedShipper?->status === 'completed';
        $return_shipper = $order->returnedShipper?->status === 'completed';

        // 2. تطبيق قواعد التحصيل
        $canCollect = match (true) {
            // حالة Delivered مع وجود مرتجع -> يتطلب تحصيل ومرتجع (إذا كان الإعداد مفعل)
            $status === 'deliverd' && $has_return => $requireShipperFirst ? ($collect_shipper && $return_shipper) : true,

            // حالة Delivered without return (false أو null) -> يتطلب تحصيل فقط (إذا كان الإعداد مفعل)
            $status === 'deliverd' && !$has_return => $requireShipperFirst ? $collect_shipper : true,

            // حالة Undelivered -> يتطلب مرتجع دا Noً (لأن الأوردر لم يسلم) وتطلب تحصيل إذا كان الإعداد مفعل
            $status === 'undelivered' => $requireShipperFirst ? ($collect_shipper && $return_shipper) : $return_shipper,

            // غير ذلك
            default => false,
        };

        return $canCollect;
    }

    /**
     * حساب مبالغ الCollect for Client
     */
    public function calculateAmounts(array $orderIds): array
    {
        $orders = Order::whereIn('id', $orderIds)->get();

        $totalAmount = 0;
        $fees = 0;
        $numberOfOrders = $orders->count();

        foreach ($orders as $order) {
            // نجمع المبلغ الإجمالي للأوردر
            if ($order->status === 'deliverd') {
                $totalAmount += $order->total_amount ?? 0;
            }
            $fees += $order->fees ?? 0;
        }

        return [
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'net_amount' => $totalAmount - $fees,
            'number_of_orders' => $numberOfOrders,
        ];
    }

    /**
     * إنشاء تحصيل جديد للعميل
     */
    public function createCollection(int $clientId, array $orderIds, ?string $collectionDate = null): CollectedClient
    {
        return DB::transaction(function () use ($clientId, $orderIds, $collectionDate) {
            $amounts = $this->calculateAmounts($orderIds);
            $orders = Order::whereIn('id', $orderIds)->get();

            // Create collection record
            $collection = CollectedClient::create([
                'client_id' => $clientId,
                'collection_date' => $collectionDate ?? Carbon::now()->toDateString(),
                'total_amount' => $amounts['total_amount'],
                'fees' => $amounts['fees'],
                'net_amount' => $amounts['net_amount'],
                'number_of_orders' => $amounts['number_of_orders'],
                'status' => CollectingStatus::PENDING->value,
            ]);

            // ربط Orderات بالتحصيل (بدون قلب الحالة لـ true)
            foreach ($orders as $order) {
                $order->update([
                    'collected_client_id' => $collection->id,
                ]);
            }

            return $collection;
        });
    }

    /**
     * Update existing collection
     */
    public function updateCollection(CollectedClient $collection, array $orderIds): CollectedClient
    {
        return DB::transaction(function () use ($collection, $orderIds) {
            // إزالة Orderات القديمة
            Order::where('collected_client_id', $collection->id)
                ->update([
                    'collected_client' => false,
                    'collected_client_date' => null,
                    'collected_client_id' => null,
                ]);

            // Calculate new amounts
            $amounts = $this->calculateAmounts($orderIds);
            $orders = Order::whereIn('id', $orderIds)->get();

            // Update collection record
            $collection->update([
                'total_amount' => $amounts['total_amount'],
                'fees' => $amounts['fees'],
                'net_amount' => $amounts['net_amount'],
                'number_of_orders' => $amounts['number_of_orders'],
            ]);

            // ربط Orderات بالتحصيل (بدون قلب الحالة لـ true)
            foreach ($orders as $order) {
                $order->update([
                    'collected_client_id' => $collection->id,
                ]);
            }

            return $collection->fresh();
        });
    }

    /**
     * Approve collection
     */
    public function approveCollection(CollectedClient $collection): CollectedClient
    {
        return DB::transaction(function () use ($collection) {
            // قلب حالة كافة الأوردرات المرتبطة لـ true عند الاعتماد فقط
            Order::where('collected_client_id', $collection->id)
                ->update([
                    'collected_client' => true,
                    'collected_client_date' => Carbon::now(),
                ]);

            $collection->update(['status' => CollectingStatus::COMPLETED->value]);
            
            return $collection->fresh();
        });
    }

    /**
     * Cancel collection
     */
    public function cancelCollection(CollectedClient $collection): CollectedClient
    {
        return DB::transaction(function () use ($collection) {
            // إزالة ربط Orderات
            Order::where('collected_client_id', $collection->id)
                ->update([
                    'collected_client' => false,
                    'collected_client_date' => null,
                    'collected_client_id' => null,
                ]);

            $collection->update(['status' => CollectingStatus::CANCELLED->value]);
            return $collection->fresh();
        });
    }

    /**
     * الحصول على تحصيNoت Client
     */
    public function getClientCollections(int $clientId): Collection
    {
        return CollectedClient::where('client_id', $clientId)
            ->with('orders')
            ->latest()
            ->get();
    }

    /**
     * Recalculate amounts for existing collection
     */
    public function recalculateCollection(CollectedClient $collection): CollectedClient
    {
        $orderIds = $collection->orders->pluck('id')->toArray();
        $amounts = $this->calculateAmounts($orderIds);

        $collection->update([
            'total_amount' => $amounts['total_amount'],
            'fees' => $amounts['fees'],
            'net_amount' => $amounts['net_amount'],
            'number_of_orders' => $amounts['number_of_orders'],
        ]);

        return $collection->fresh();
    }
}
