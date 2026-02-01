<?php

namespace App\Models;

use App\Enums\CollectingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnedShipper extends Model
{
    use HasFactory;

    protected $table = 'returned_shippers';

    protected $fillable = [
        'shipper_id',
        'return_date',
        'number_of_orders',
        'status',
        'notes',
    ];

    protected $casts = [
        'return_date'      => 'date',
        'number_of_orders' => 'integer',
    ];

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
        return $this->hasMany(Order::class, 'returned_shipper_id');
    }

    /**
     * Scopes
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * النص العربي للحالة
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'قيد المراجعة',
            'completed' => 'تم الاعتماد ✅',
            'cancelled' => 'ملغى ❌',
            default => 'غير معروف',
        };
    }
}
