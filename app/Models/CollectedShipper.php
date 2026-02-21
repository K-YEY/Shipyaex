<?php

namespace App\Models;

use App\Enums\CollectingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectedShipper extends Model
{
    use HasFactory;

    protected $table = 'collected_shipper';

    protected $fillable = [
        'shipper_id',
        'collection_date',
        'total_amount',
        'number_of_orders',
        'shipper_fees',
        'fees',
        'net_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'collection_date'  => 'date',
        'total_amount'     => 'decimal:2',
        'shipper_fees'     => 'decimal:2',
        'fees'             => 'decimal:2',
        'net_amount'       => 'decimal:2',
        'number_of_orders' => 'integer',
    ];

    /**
     * ============================================
     * العNoقات
     * ============================================
     */

    /**
     * العNoقة مع Shipper
     */
    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    /**
     * العNoقة مع Orderات
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'collected_shipper_id');
    }

    /**
     * ============================================
     * Scopes
     * ============================================
     */

    /**
     * التحصيNoت الHoldة
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CollectingStatus::PENDING->value);
    }

    /**
     * التحصيNoت الCompletedة
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', CollectingStatus::COMPLETED->value);
    }

    /**
     * التحصيNoت الCancelledة
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', CollectingStatus::CANCELLED->value);
    }

    /**
     * التحصيNoت حسب Shipper
     */
    public function scopeForShipper(Builder $query, int $shipperId): Builder
    {
        return $query->where('shipper_id', $shipperId);
    }

    /**
     * التحصيNoت في تاريخ معين
     */
    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('collection_date', $date);
    }

    /**
     * التحصيNoت في نطاق تاريخي
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('collection_date', [$from, $to]);
    }

    /**
     * ============================================
     * Accessors & Mutators
     * ============================================
     */

    /**
     * الحصول على لون Status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * الحصول على أيقونة Status
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'pending' => 'heroicon-o-clock',
            'completed' => 'heroicon-o-check-circle',
            'cancelled' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * الحصول على نص Status بالعربي
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'قيد انتظار',
            'completed' => 'تم',
            'cancelled' => 'تم الالغاء',
            default => 'غير معروف',
        };
    }

    /**
     * ============================================
     * Helper Methods
     * ============================================
     */

    /**
     * هل التحصيل Hold؟
     */
    public function isPending(): bool
    {
        return $this->status === CollectingStatus::PENDING->value;
    }

    /**
     * هل التحصيل Completed؟
     */
    public function isCompleted(): bool
    {
        return $this->status === CollectingStatus::COMPLETED->value;
    }

    /**
     * هل التحصيل Cancelled؟
     */
    public function isCancelled(): bool
    {
        return $this->status === CollectingStatus::CANCELLED->value;
    }

    /**
     * هل يمكن Edit التحصيل؟
     */
    public function isEditable(): bool
    {
        return $this->isPending();
    }

    /**
     * هل يمكن Print الفاتورة؟
     */
    public function isPrintable(): bool
    {
        return $this->isCompleted();
    }

    /**
     * إعادة حساب المبالغ
     */
    public function recalculateAmounts(): void
    {
        $orders = $this->orders()->with('client')->get();
        
        $totalAmount = 0;
        $shipperFees = 0;
        $fees = 0;
        $clientNames = [];

        foreach ($orders as $order) {
            if ($order->status === 'deliverd') {
                $totalAmount += $order->total_amount ?? 0;
            }
            $shipperFees += $order->shipper_fees ?? 0;
            $fees += $order->fees ?? 0;
            if ($order->client?->name) {
                $clientNames[] = $order->client->name;
            }
        }

        $uniqueClients = array_unique($clientNames);
        $clientsList = implode(', ', $uniqueClients);
        
        // تجهيز الملاحظات بحيث تشمل أسماء العملاء بشكل منظم
        $orderInfo = "العملاء: " . $clientsList;

        $this->update([
            'total_amount' => $totalAmount,
            'shipper_fees' => $shipperFees,
            'fees' => $fees,
            'net_amount' => $totalAmount - $shipperFees,
            'number_of_orders' => $orders->count(),
            'notes' => $orderInfo,
        ]);
    }

    /**
     * ============================================
     * Events
     * ============================================
     */

    /**
     * حساب الصافي تلقائيًا
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            $model->net_amount = $model->total_amount - $model->shipper_fees;
        });
    }
}
