<?php

namespace App\Enums;

enum OrderStatus: string
{
    case OUT_FOR_DELIVERY = 'out for delivery';
    case DELIVERED = 'deliverd';
    case HOLD = 'hold';
    case UNDELIVERED = 'undelivered';

    public function label(): string
    {
        return match($this) {
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::HOLD => 'Hold',
            self::UNDELIVERED => 'لم يDelivered',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OUT_FOR_DELIVERY => 'info',
            self::DELIVERED => 'success',
            self::HOLD => 'warning',
            self::UNDELIVERED => 'danger',
        };
    }

    /**
     * هل Order يحتاج تحصيل Shipper فقط (without return)؟
     * Delivered without return
     */
    public function requiresShipperCollectOnly(): bool
    {
        return $this === self::DELIVERED;
    }

    /**
     * هل Order يحتاج تحصيل Shipper + مرتجع؟
     * Undelivered أو Delivered with return
     */
    public function requiresBothCollections(): bool
    {
        return $this === self::UNDELIVERED;
    }
}
