<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'old_status',
        'note',
        'changed_by',
        'action_type',
    ];

    /**
     * Get the order that owns the history.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user that made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get action type label
     */
    public function getActionTypeLabelAttribute(): string
    {
        return match($this->action_type) {
            'created' => '🆕 إنشاء Order',
            'status_changed' => '🔄 تغيير Status',
            'collected_shipper' => '📦 تحصيل من Shipper',
            'collected_client' => '💰 Collect for Client',
            'return_shipper' => '↩️ مرتجع Shipper',
            'return_client' => '↩️ مرتجع Client',
            'delivered' => '✅ تسليم',
            'shipper_assigned' => '🚚 Assign Shipper',
            'edited' => '✏️ Edit',
            default => '📝 تحديث',
        };
    }
}
