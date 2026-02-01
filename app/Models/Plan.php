<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    protected $table = "plan";
    protected $fillable = [
        'name',
        'order_count',

    ];

    /**
     * Get the plan prices for the plan.
     */
    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
