<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefusedReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reason) {
            if (empty($reason->slug)) {
                $reason->slug = Str::slug($reason->name);
            }
        });

        static::updating(function ($reason) {
            if ($reason->isDirty('name') && empty($reason->slug)) {
                $reason->slug = Str::slug($reason->name);
            }
        });
    }

    /**
     * Scope for active reasons only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered reasons
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the order statuses associated with this refused reason
     */
    public function orderStatuses()
    {
        return $this->belongsToMany(OrderStatus::class, 'order_status_refused_reason')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
