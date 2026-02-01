<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnedClient extends Model
{
    use HasFactory;

    protected $table = 'returned_clients';

    protected $fillable = [
        'client_id',
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
     * Relationship with Client
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Relationship with Orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'returned_client_id');
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
     * Status label attribute
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'قيد المراجعة',
            'completed' => 'تم الاعتماد ✅',
            'cancelled' => 'ملغى ❌',
            default => 'Unknown',
        };
    }
}
