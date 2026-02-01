<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'is_active',
        'sort_order',
        'clear_refused_reasons',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'clear_refused_reasons' => 'boolean',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($status) {
            if (empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }
        });

        static::updating(function ($status) {
            if ($status->isDirty('name') && empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }
        });
    }

    /**
     * Scope for active statuses only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered statuses
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the refused reasons associated with this order status
     */
    public function refusedReasons()
    {
        return $this->belongsToMany(RefusedReason::class, 'order_status_refused_reason')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
