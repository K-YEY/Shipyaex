<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CachedOrderService
{
    /**
     * Cache duration in seconds (5 minutes)
     */
    const CACHE_TTL = 300;

    /**
     * Cache tags for organized cache management
     */
    const CACHE_TAGS = ['orders', 'dashboard', 'statistics'];

    /**
     * Get dashboard statistics with caching
     * 
     * @param int|null $userId User ID for role-based filtering
     * @param string|null $role User role (client, shipper, admin)
     * @return array Dashboard statistics
     */
    public static function getDashboardStats(?int $userId = null, ?string $role = null): array
    {
        $cacheKey = "dashboard_stats_{$role}_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $role) {
            $query = Order::query();
            
            // Apply role-based filtering
            if ($role === 'client') {
                $query->where('client_id', $userId);
            } elseif ($role === 'shipper') {
                $query->where('shipper_id', $userId);
            }
            
            return $query->selectRaw('
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = "deliverd" THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = "undelivered" THEN 1 END) as undelivered_orders,
                COUNT(CASE WHEN status = "hold" THEN 1 END) as hold_orders,
                COUNT(CASE WHEN status = "out for delivery" THEN 1 END) as out_for_delivery_orders,
                COUNT(CASE WHEN collected_shipper = 1 THEN 1 END) as collected_shipper_orders,
                COUNT(CASE WHEN collected_client = 1 THEN 1 END) as collected_client_orders,
                COUNT(CASE WHEN has_return = 1 THEN 1 END) as has_return_orders,
                SUM(total_amount) as total_amount,
                SUM(fees) as total_fees,
                SUM(shipper_fees) as total_shipper_fees,
                SUM(cop) as total_cop,
                SUM(cod) as total_cod
            ')->first()->toArray();
        });
    }

    /**
     * Get table totals with caching (for column headers)
     * 
     * @param array $filters Applied filters
     * @return array Column totals
     */
    public static function getTableTotals(array $filters = []): array
    {
        $cacheKey = 'table_totals_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Order::query();
            
            // Apply filters
            foreach ($filters as $field => $value) {
                if (!is_null($value)) {
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            
            return $query->selectRaw('
                SUM(total_amount) as total_amount,
                SUM(fees) as fees,
                SUM(shipper_fees) as shipper_fees,
                SUM(cop) as cop,
                SUM(cod) as cod,
                COUNT(*) as count
            ')->first()->toArray();
        });
    }

    /**
     * Get orders available for shipper collecting (cached)
     * 
     * @param int $shipperId Shipper ID
     * @return int Count of available orders
     */
    public static function getAvailableForShipperCollecting(int $shipperId): int
    {
        $cacheKey = "available_shipper_collecting_{$shipperId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shipperId) {
            return Order::availableForShipperCollecting()
                ->where('shipper_id', $shipperId)
                ->count();
        });
    }

    /**
     * Get orders available for client collecting (cached)
     * 
     * @param int $clientId Client ID
     * @return int Count of available orders
     */
    public static function getAvailableForClientCollecting(int $clientId): int
    {
        $cacheKey = "available_client_collecting_{$clientId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($clientId) {
            return Order::availableForClientCollecting()
                ->where('client_id', $clientId)
                ->count();
        });
    }

    /**
     * Get recent orders with relationships (cached)
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param int $limit Number of orders to fetch
     * @return \Illuminate\Support\Collection
     */
    public static function getRecentOrders(int $userId, string $role, int $limit = 10)
    {
        $cacheKey = "recent_orders_{$role}_{$userId}_{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $role, $limit) {
            $query = Order::query()
                ->with([
                    'client:id,name,phone',
                    'shipper:id,name,phone',
                    'governorate:id,name',
                    'city:id,name',
                ])
                ->latest();
            
            if ($role === 'client') {
                $query->where('client_id', $userId);
            } elseif ($role === 'shipper') {
                $query->where('shipper_id', $userId);
            }
            
            return $query->limit($limit)->get();
        });
    }

    /**
     * Get status distribution (cached)
     * 
     * @param int|null $userId User ID for filtering
     * @param string|null $role User role
     * @return array Status counts
     */
    public static function getStatusDistribution(?int $userId = null, ?string $role = null): array
    {
        $cacheKey = "status_distribution_{$role}_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $role) {
            $query = Order::query();
            
            if ($role === 'client') {
                $query->where('client_id', $userId);
            } elseif ($role === 'shipper') {
                $query->where('shipper_id', $userId);
            }
            
            return $query->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });
    }

    /**
     * Clear all order-related caches
     */
    public static function clearCache(): void
    {
        // Clear tagged cache if using Redis/Memcached
        if (config('cache.default') === 'redis') {
            Cache::tags(self::CACHE_TAGS)->flush();
        } else {
            // Fallback: Clear specific patterns
            Cache::flush(); // Use with caution in production
        }
    }

    /**
     * Clear specific user cache
     * 
     * @param int $userId User ID
     * @param string $role User role
     */
    public static function clearUserCache(int $userId, string $role): void
    {
        $patterns = [
            "dashboard_stats_{$role}_{$userId}",
            "recent_orders_{$role}_{$userId}_*",
            "status_distribution_{$role}_{$userId}",
            "available_shipper_collecting_{$userId}",
            "available_client_collecting_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For wildcard patterns, you'd need Redis SCAN
                // This is a simplified version
                Cache::forget(str_replace('*', '10', $pattern));
                Cache::forget(str_replace('*', '25', $pattern));
                Cache::forget(str_replace('*', '50', $pattern));
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear cache for specific order
     * 
     * @param \App\Models\Order $order Order model
     */
    public static function clearOrderCache(Order $order): void
    {
        // Clear user-specific caches
        if ($order->client_id) {
            self::clearUserCache($order->client_id, 'client');
        }
        
        if ($order->shipper_id) {
            self::clearUserCache($order->shipper_id, 'shipper');
        }
        
        // Clear table totals (they depend on filters, so clear all)
        Cache::flush(); // In production, use more targeted approach
    }

    /**
     * Warm cache for active users
     * Call this from a scheduled command
     */
    public static function warmCache(): void
    {
        $activeUsers = \App\Models\User::query()
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '>', now()->subDays(7))
            ->with('roles')
            ->get();

        foreach ($activeUsers as $user) {
            $role = $user->roles->first()?->name;
            
            if (in_array($role, ['client', 'shipper', 'admin'])) {
                // Pre-cache dashboard stats
                self::getDashboardStats($user->id, $role);
                
                // Pre-cache recent orders
                self::getRecentOrders($user->id, $role);
                
                // Pre-cache status distribution
                self::getStatusDistribution($user->id, $role);
            }
        }
    }
}
