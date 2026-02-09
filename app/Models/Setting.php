<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function () {
            Cache::forget('settings_cache');
        });

        static::deleted(function () {
            Cache::forget('settings_cache');
        });
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $settings = Cache::remember('settings_cache', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings as array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('settings_cache', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('settings_cache');
    }

    /**
     * Get default settings values.
     */
    public static function getDefaults(): array
    {
        return [
            // Order Settings
            'order_prefix' => 'SHP',
            'order_digits' => '5',

            // Working Hours - Orders
            'working_hours_orders_start' => '08:00',
            'working_hours_orders_end' => '22:00',

            // Collection Settings
            // هل يجب تحصيل Shipper أوNoً قبل تحصيل Client؟
            'require_shipper_collection_first' => 'yes', // yes أو no
            'order_follow_up_hours' => '48',
            'welcome_plans' => 'all', // all or IDs separated by comma
        ];
    }

    /**
     * Seed default settings if they don't exist.
     */
    public static function seedDefaults(): void
    {
        $defaults = static::getDefaults();
        $existing = static::pluck('key')->toArray();

        foreach ($defaults as $key => $value) {
            if (!in_array($key, $existing)) {
                static::create(['key' => $key, 'value' => $value]);
            }
        }

        static::clearCache();
    }
}
