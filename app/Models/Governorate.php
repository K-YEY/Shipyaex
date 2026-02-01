<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'follow_up_hours',
        'shipper_id'
    ];

    /**
     * Get the cities for the governorate.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }
}
