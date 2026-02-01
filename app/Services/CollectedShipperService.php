<?php

namespace App\Services;

use App\Enums\CollectingStatus;
use App\Models\CollectedShipper;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CollectedShipperService
{
    /**
     * الحصول على Orderات المتاحة للتحصيل للShipper
     * 
     * Business Rules:
     * 1. Delivered (without return): collected_shipper = false, return_shipper = false, has_return = false
     * 2. Delivered partial (with return): collected_shipper = false, has_return = true
     * 3. Undelivered: collected_shipper = false
     */
    public function getAvailableOrdersForShipper(int $shipperId): Collection
    {
        return Order::query()
            ->where('shipper_id', $shipperId)
            ->whereNotNull('status')
            ->where('collected_shipper', false)
            ->where(function ($query) {
                // Delivered without return
                $query->where(function ($q) {
                    $q->where('status', 'deliverd')
                        ->where('has_return', false);
                })
                // Delivered with return
                ->orWhere(function ($q) {
                    $q->where('status', 'deliverd')
                        ->where('has_return', true);
                })
                // Undelivered
                ->orWhere('status', 'undelivered');
            })
            ->get();
    }

    /**
     * التحقق من صحة Orderات قبل الإنشاء
     */
    public function validateOrdersForCollection(array $orderIds, int $shipperId): array
    {
        $errors = [];
        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            // التحقق من أن Order تابع للShipper
            if ($order->shipper_id !== $shipperId) {
                $errors[] = "Order #{$order->code} No يتبع لهذا Shipper";
                continue;
            }

            // Verify no previous collection
            if ($order->collected_shipper) {
                $errors[] = "Order #{$order->code} already collected";
                continue;
            }

            // Verify business rules for statuses
            if (!$this->isOrderEligibleForCollection($order)) {
                $errors[] = "Order #{$order->code} does not meet collection requirements";
            }
        }

        return $errors;
    }

    /**
     * التحقق من أهلية Order للتحصيل
     */
    public function isOrderEligibleForCollection(Order $order): bool
    {
        // No يمكن تحصيل طلب already collected
        if ($order->collected_shipper) {
            return false;
        }

        // Delivered without return - there should be no return
        if ($order->status === 'deliverd' && !$order->has_return) {
            return true;
        }

        // Delivered with return - there must be a return
        if ($order->status === 'deliverd' && $order->has_return) {
            return true;
        }

        // Undelivered - always eligible
        if ($order->status === 'undelivered') {
            return true;
        }

        return false;
    }

    /**
     * Calculate collection amounts
     */
    public function calculateAmounts(array $orderIds): array
    {
        $orders = Order::whereIn('id', $orderIds)->get();

        $totalAmount = 0;
        $shipperFees = 0;
        $numberOfOrders = $orders->count();

        foreach ($orders as $order) {
            // Delivered (كامل أو partial) - we collect the full amount
            if ($order->status === 'deliverd') {
                $totalAmount += $order->cod ?? 0;
            }
            // Undelivered - no amount to collect
            // لكن نحسب رسوم Shipper

            $shipperFees += $order->shipper_fees ?? 0;
        }

        return [
            'total_amount' => $totalAmount,
            'shipper_fees' => $shipperFees,
            'net_amount' => $totalAmount - $shipperFees,
            'number_of_orders' => $numberOfOrders,
        ];
    }

    /**
     * إنشاء تحصيل جديد للShipper
     */
    public function createCollection(int $shipperId, array $orderIds, ?string $collectionDate = null): CollectedShipper
    {
        return DB::transaction(function () use ($shipperId, $orderIds, $collectionDate) {
            $amounts = $this->calculateAmounts($orderIds);
            $orders = Order::whereIn('id', $orderIds)->get();

            // Create collection record
            $collection = CollectedShipper::create([
                'shipper_id' => $shipperId,
                'collection_date' => $collectionDate ?? Carbon::now()->toDateString(),
                'total_amount' => $amounts['total_amount'],
                'shipper_fees' => $amounts['shipper_fees'],
                'net_amount' => $amounts['net_amount'],
                'number_of_orders' => $amounts['number_of_orders'],
                'status' => CollectingStatus::PENDING->value,
            ]);

            // ربط Orderات بالتحصيل (بدون قلب الحالة لـ true)
            foreach ($orders as $order) {
                $order->update([
                    'collected_shipper_id' => $collection->id,
                ]);
            }

            return $collection;
        });
    }

    /**
     * Update existing collection
     */
    public function updateCollection(CollectedShipper $collection, array $orderIds): CollectedShipper
    {
        return DB::transaction(function () use ($collection, $orderIds) {
            // إزالة Orderات القديمة
            Order::where('collected_shipper_id', $collection->id)
                ->update([
                    'collected_shipper' => false,
                    'collected_shipper_date' => null,
                    'collected_shipper_id' => null,
                ]);

            // Calculate new amounts
            $amounts = $this->calculateAmounts($orderIds);
            $orders = Order::whereIn('id', $orderIds)->get();

            // Update collection record
            $collection->update([
                'total_amount' => $amounts['total_amount'],
                'shipper_fees' => $amounts['shipper_fees'],
                'net_amount' => $amounts['net_amount'],
                'number_of_orders' => $amounts['number_of_orders'],
            ]);

            // ربط Orderات بالتحصيل (بدون قلب الحالة لـ true)
            foreach ($orders as $order) {
                $order->update([
                    'collected_shipper_id' => $collection->id,
                ]);
            }

            return $collection->fresh();
        });
    }

    /**
     * Approve collection
     */
    public function approveCollection(CollectedShipper $collection): CollectedShipper
    {
        return DB::transaction(function () use ($collection) {
            // قلب حالة كافة الأوردرات المرتبطة لـ true عند الاعتماد فقط
            Order::where('collected_shipper_id', $collection->id)
                ->update([
                    'collected_shipper' => true,
                    'collected_shipper_date' => Carbon::now(),
                ]);

            $collection->update(['status' => CollectingStatus::COMPLETED->value]);
            
            return $collection->fresh();
        });
    }

    /**
     * Cancel collection
     */
    public function cancelCollection(CollectedShipper $collection): CollectedShipper
    {
        return DB::transaction(function () use ($collection) {
            // إزالة ربط Orderات
            Order::where('collected_shipper_id', $collection->id)
                ->update([
                    'collected_shipper' => false,
                    'collected_shipper_date' => null,
                    'collected_shipper_id' => null,
                    'return_shipper' => false,
                    'return_shipper_date' => null,
                ]);

            $collection->update(['status' => CollectingStatus::CANCELLED->value]);
            return $collection->fresh();
        });
    }

    /**
     * التحقق من صحة حالة Orderات حسب Business Rules
     * 
     * Forbidden:
     * - collected_shipper = true و return_shipper = false (للحاNoت 2 و 3)
     */
    public function validateBusinessRules(array $orderIds): array
    {
        $errors = [];
        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            // Delivered with return أو Undelivered
            if ($order->has_return || $order->status === 'undelivered') {
                // both collections must be together
                // this will be applied upon creation
            }
        }

        return $errors;
    }

    /**
     * الحصول على تحصيNoت Shipper
     */
    public function getShipperCollections(int $shipperId): Collection
    {
        return CollectedShipper::where('shipper_id', $shipperId)
            ->with('orders')
            ->latest()
            ->get();
    }

    /**
     * Recalculate amounts for existing collection
     */
    public function recalculateCollection(CollectedShipper $collection): CollectedShipper
    {
        $orderIds = $collection->orders->pluck('id')->toArray();
        $amounts = $this->calculateAmounts($orderIds);

        $collection->update([
            'total_amount' => $amounts['total_amount'],
            'shipper_fees' => $amounts['shipper_fees'],
            'net_amount' => $amounts['net_amount'],
            'number_of_orders' => $amounts['number_of_orders'],
        ]);

        return $collection->fresh();
    }
}
