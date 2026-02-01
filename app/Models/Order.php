<?php

namespace App\Models;

use App\Models\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // جدول مخصص لأنه مش plural
    protected $table = 'order';

    // الحقول القابلة للEdit
    protected $fillable = [
        'code',
        'external_code',
        'shipper_date',
        'name',
        'phone',
        'phone_2',
        'address',
        'governorate_id',
        'city_id',
        'total_amount',
        'fees',
        'shipper_fees',
        'cop',
        'cod',
        'status',
        'status_note',
        'order_note',
        'allow_open',
        'shipper_id',
        'collected_shipper',
        'collected_shipper_date',
        'collected_shipper_id',
        'return_shipper',
        'return_shipper_date',
        'has_return',
        'has_return_date',
        'collected_client',
        'collected_client_date',
        'collected_client_id',
        'return_client',
        'return_client_date',
        'client_id',
        'returned_shipper_id',
        'returned_client_id',
    ];

    /**
     * العNoقة with return Shipper
     */
    public function returnedShipper()
    {
        return $this->belongsTo(ReturnedShipper::class, 'returned_shipper_id');
    }

    /**
     * Relationship with returned Client
     */
    public function returnedClient()
    {
        return $this->belongsTo(ReturnedClient::class, 'returned_client_id');
    }

    /**
     * Scope for orders available for shipper return
     */
    public function scopeAvailableForShipperReturn(Builder $query): Builder
    {
        return $query->whereNull('returned_shipper_id')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('status', 'deliverd')
                        ->where('has_return', true);
                })
                ->orWhere('status', 'undelivered');
            });
    }

    // التحويNoت للـ dates والـ booleans
    protected $casts = [
        'shipper_date' => 'datetime',
        'collected_shipper_date' => 'datetime',
        'return_shipper_date' => 'datetime',
        'has_return_date' => 'datetime',
        'collected_client_date' => 'datetime',
        'return_client_date' => 'datetime',
        'collected_shipper' => 'boolean',
        'return_shipper' => 'boolean',
        'has_return' => 'boolean',
        'collected_client' => 'boolean',
        'return_client' => 'boolean',
        'allow_open' => 'boolean',
        'status_note' => 'array',
        'total_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'shipper_fees' => 'decimal:2',
        'cop' => 'decimal:2',
        'cod' => 'decimal:2',
    ];

    /**
     * العNoقات
     */

    // الشيبّر
    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    // Client
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Governorate
    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    // City
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function collectedShipper()
    {
        return $this->belongsTo(CollectedShipper::class, 'collected_shipper_id');
    }

    public function collectedClient()
    {
        return $this->belongsTo(CollectedClient::class, 'collected_client_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Get the order status model
     */
    public function orderStatus()
    {
        return $this->hasOne(OrderStatus::class, 'slug', 'status');
    }

    /**
     * ============================================
     * Scopes للتحصيل
     * ============================================
     */

    /**
     * Orderات المتاحة لتحصيل Shipper
     */
    public function scopeAvailableForShipperCollecting(Builder $query): Builder
    {
        return $query
            ->whereNotNull('status')
            ->where('collected_shipper', false)
            ->whereNull('collected_shipper_id')
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
            });
    }

    /**
     * Orderات المتاحة لتحصيل Client
     * Apply double verification binding conditions
     */
    public function scopeAvailableForClientCollecting(Builder $query): Builder
    {
        return $query->whereNotNull('status')
            ->whereNull('collected_client_id')
            ->where(function ($q) {
                // Status 1: Delivered مع has_return = true -> يحتاج تحصيل ومرتجع Approvedين
                $q->where(function ($deliveredWithReturn) {
                    $deliveredWithReturn->where('status', 'deliverd')
                        ->where('has_return', true)
                        ->whereNotNull('collected_shipper_id')
                        ->whereHas('collectedShipper', fn($sq) => $sq->where('status', 'completed'))
                        ->whereNotNull('returned_shipper_id')
                        ->whereHas('returnedShipper', fn($sq) => $sq->where('status', 'completed'));
                })
                // Status 2: Delivered بدون has_return -> يحتاج تحصيل Approved فقط
                ->orWhere(function ($deliveredNoReturn) {
                    $deliveredNoReturn->where('status', 'deliverd')
                        ->where(function ($hasReturnFalse) {
                            $hasReturnFalse->where('has_return', false)
                                ->orWhereNull('has_return');
                        })
                        ->whereNotNull('collected_shipper_id')
                        ->whereHas('collectedShipper', fn($sq) => $sq->where('status', 'completed'));
                })
                // Status 3: Undelivered -> يحتاج تحصيل ومرتجع Approvedين
                ->orWhere(function ($undelivered) {
                    $undelivered->where('status', 'undelivered')
                        ->whereNotNull('collected_shipper_id')
                        ->whereHas('collectedShipper', fn($sq) => $sq->where('status', 'completed'))
                        ->whereNotNull('returned_shipper_id')
                        ->whereHas('returnedShipper', fn($sq) => $sq->where('status', 'completed'));
                });
            });
    }

    /**
     * Orderات حسب Shipper
     */
    public function scopeForShipper(Builder $query, int $shipperId): Builder
    {
        return $query->where('shipper_id', $shipperId);
    }

    /**
     * Orderات حسب Client
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Orderات التي تم تحصيلها من Shipper
     */
    public function scopeCollectedByShipper(Builder $query): Builder
    {
        return $query->where('collected_shipper', true);
    }

    /**
     * Orderات التي تم تحصيلها للعميل
     */
    public function scopeCollectedByClient(Builder $query): Builder
    {
        return $query->where('collected_client', true);
    }

    /**
     * Orderات التي بها مرتجع
     */
    public function scopeWithReturn(Builder $query): Builder
    {
        return $query->where('has_return', true);
    }

    /**
     * Orderات الغير مسلمة
     */
    public function scopeUndelivered(Builder $query): Builder
    {
        return $query->where('status', 'undelivered');
    }

    /**
     * Orderات المسلمة
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'deliverd');
    }

    /**
     * ============================================
     * Helper Methods
     * ============================================
     */

    /**
     * هل Order متاح لتحصيل Shipper؟
     */
    public function isAvailableForShipperCollecting(): bool
    {
        if ($this->collected_shipper) {
            return false;
        }

        // Delivered (كامل أو partial)
        if ($this->status === 'deliverd') {
            return true;
        }

        // Undelivered
        if ($this->status === 'undelivered') {
            return true;
        }

        return false;
    }

    /**
     * هل Order متاح لتحصيل Client؟
     */
    public function isAvailableForClientCollecting(): bool
    {
        // الشروط الأساسية
        if (is_null($this->status) || !is_null($this->collected_client_id)) {
            return false;
        }

        // فحص وجود واعتماد collected_shipper
        if (!$this->collected_shipper_id || !$this->collectedShipper || $this->collectedShipper->status !== 'completed') {
            return false;
        }

        // Status 1: Delivered مع has_return = true
        if ($this->status === 'deliverd' && $this->has_return) {
            return $this->returned_shipper_id 
                && $this->returnedShipper 
                && $this->returnedShipper->status === 'completed';
        }

        // Status 2: Delivered بدون has_return
        if ($this->status === 'deliverd' && !$this->has_return) {
            return true;
        }

        // Status 3: Undelivered
        if ($this->status === 'undelivered') {
            return $this->returned_shipper_id 
                && $this->returnedShipper 
                && $this->returnedShipper->status === 'completed';
        }

        return false;
    }

    /**
     * هل Order يتطلب تحصيل المرتجع من Shipper؟
     */
    public function requiresReturnShipper(): bool
    {
        return $this->has_return || $this->status === 'undelivered';
    }

    /**
     * الحصول على نوع التحصيل المطلوب
     *
     * @return string 'shipper_only' | 'shipper_and_return'
     */
    public function getCollectionType(): string
    {
        if ($this->status === 'deliverd' && !$this->has_return) {
            return 'shipper_only';
        }

        return 'shipper_and_return';
    }

    /**
     * الحصول على حالة لون الـ Status
     */
    public function getStatusColorAttribute(): string
    {
        // Try to get color from OrderStatus model
        if ($this->orderStatus) {
            return $this->orderStatus->color ?? 'gray';
        }

        // Fallback to hardcoded colors if OrderStatus not found
        return match($this->status) {
            'out for delivery' => 'info',
            'deliverd' => 'success',
            'hold' => 'warning',
            'undelivered' => 'danger',
            default => 'gray',
        };
    }

    /**
     * ============================================
     * حسابات تلقائية - Backend Calculations
     * ============================================
     */

    /**
     * حساب COD (المبلغ المستحق للعميل)
     * COD = total_amount - fees
     * يسمح بالقيم السالبة (لو total_amount = 0 و fees = 100 يكون COD = -100)
     */
    public static function calculateCod(?float $totalAmount, ?float $fees): float
    {
        $total = $totalAmount ?? 0;
        $feesValue = $fees ?? 0;

        return $total - $feesValue;
    }

    /**
     * حساب COP (Company Fees)
     * COP = fees - shipper_fees
     * يسمح بالقيم السالبة
     */
    public static function calculateCop(?float $fees, ?float $shipperFees): float
    {
        $feesValue = $fees ?? 0;
        $shipperValue = $shipperFees ?? 0;

        return $feesValue - $shipperValue;
    }

    /**
     * إعادة حساب COD و COP وSaveها
     */
    public function recalculateFinancials(): void
    {
        $this->cod = self::calculateCod($this->total_amount, $this->fees);
        $this->cop = self::calculateCop($this->fees, $this->shipper_fees);
    }

    /**
     * Boot method - يتم تشغيله تلقائياً عند Save Order
     */
    protected static function boot()
    {
        parent::boot();

        // عند الإنشاء أو التحديث، نحسب COD و COP تلقائياً
        static::saving(function (Order $order) {
            $order->cod = self::calculateCod($order->total_amount, $order->fees);
            $order->cop = self::calculateCop($order->fees, $order->shipper_fees);

            // تعيين تاريخ Shipper عند تغييره (أو تصفيره إذا تم Delete Shipper)
            if ($order->isDirty('shipper_id')) {
                $order->shipper_date = $order->shipper_id ? now() : null;
            }
        });

        // ⚡ PERFORMANCE OPTIMIZATION: Clear cache when order is saved
        static::saved(function (Order $order) {
            // Check if CachedOrderService exists before using it
            if (class_exists(\App\Services\CachedOrderService::class)) {
                \App\Services\CachedOrderService::clearCache();
                
                if ($order->client_id) {
                    \App\Services\CachedOrderService::clearUserCache($order->client_id, 'client');
                }
                if ($order->shipper_id) {
                    \App\Services\CachedOrderService::clearUserCache($order->shipper_id, 'shipper');
                }
            }
        });

        // ⚡ PERFORMANCE OPTIMIZATION: Clear cache when order is deleted
        static::deleted(function (Order $order) {
            if (class_exists(\App\Services\CachedOrderService::class)) {
                \App\Services\CachedOrderService::clearCache();
            }
        });
    }
    /**
     * Accessor for Net Fees (Virtual Attribute)
     * Net Fees = Total Amount - Shipper Fees
     */
    public function getNetFeesAttribute()
    {
        return $this->total_amount - ($this->shipper_fees ?? 0);
    }

    /**
     * Mutator for Net Fees
     * When Net Fees is updated, we update Total Amount
     */
    public function setNetFeesAttribute($value)
    {
        $this->attributes['total_amount'] = $value + ($this->shipper_fees ?? 0);
        // We don't set 'net_fees' or 'cod' directly here because boot/saving event handles cod calculation
        // But since this might be called by mass assignment or direct set, we rely on saving event.
    }
}
