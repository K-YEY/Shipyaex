<?php

namespace App\Models;

use App\Enums\CollectingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectedClient extends Model
{
    use HasFactory;

    protected $table = 'collected_client';

    protected $fillable = [
        'client_id',
        'collection_date',
        'total_amount',
        'number_of_orders',
        'fees',
        'net_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'collection_date'  => 'date',
        'total_amount'     => 'decimal:2',
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
     * العNoقة مع Client
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * العNoقة مع Orderات
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'collected_client_id');
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
     * التحصيNoت حسب Client
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
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
     * إعادة حساب المبالغ - تحسين الأداء باستخدام SQL
     */
    public function recalculateAmounts(): void
    {
        $stats = $this->orders()
            ->selectRaw('SUM(CASE WHEN status IN ("deliverd", "undelivered") THEN total_amount ELSE 0 END) as total_amount')
            ->selectRaw('SUM(fees) as fees')
            ->selectRaw('COUNT(*) as count')
            ->first();

        $totalAmount = (float) ($stats->total_amount ?? 0);
        $fees = (float) ($stats->fees ?? 0);

        $this->update([
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'net_amount' => $totalAmount - $fees,
            'number_of_orders' => (int) ($stats->count ?? 0),
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
            $model->net_amount = $model->total_amount - $model->fees;
        });
    }
}
