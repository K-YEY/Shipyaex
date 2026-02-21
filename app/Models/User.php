<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
//TODO: create modal for clients and shippers from users table DB
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'plan_id',
        'commission',
        'is_blocked',
        'address',
        'password',
        'push_subscription',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [

        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }
    
    /**
     * Get the name to display in Filament
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        return $this->name ?? $this->username;
    }
    
    public function canAccessPanel(Panel $panel): bool
    {

        return true;
    }

      public function shippingContents()
    {
        return $this->belongsToMany(
            ShippingContent::class,
            'client_shipping_content',
            'client_id',
            'shipping_content_id'
        )->withTimestamps();
    }
    
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    
    /**
     * الأوردرات الخاصة بShipper
     */
    public function shipperOrders()
    {
        return $this->hasMany(Order::class, 'shipper_id');
    }
    
    /**
     * الأوردرات الخاصة بClient
     */
    public function clientOrders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    /**
     * Cache for role checks to avoid redundant DB queries per request
     */
    protected array $memoizedRoles = [];

    /**
     * Role checking helpers integrated with Filament Shield
     * These allow using ANY role name as long as it has the corresponding permission
     */
    public function isAdmin(): bool
    {
        return $this->memoizedRoles['admin'] ??= (
            $this->can('Access:Admin') || 
            $this->hasRole(config('filament-shield.super_admin.name', 'super_admin')) ||
            $this->hasRole('admin')
        );
    }

    public function isClient(): bool
    {
        return $this->memoizedRoles['client'] ??= (
            $this->can('Access:Client') || $this->hasRole('client')
        );
    }

    public function isShipper(): bool
    {
        return $this->memoizedRoles['shipper'] ??= (
            $this->can('Access:Shipper') || $this->hasRole('shipper')
        );
    }

    /**
     * الحصول على جميع المديرين لاستلام الإشعارات
     */
    public static function getAdmins()
    {
        return \Illuminate\Support\Facades\Cache::remember('system_admins_list', 3600, function () {
            $admins = self::permission('Access:Admin')->get();
            if ($admins->isEmpty()) {
                $admins = self::role(['admin', 'super_admin'])->get();
            }
            return $admins;
        });
    }
}
