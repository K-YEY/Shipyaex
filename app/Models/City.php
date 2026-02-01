<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;
    protected $table = 'city';
    protected $fillable = [
        'name',
        'governorate_id',

    ];

    /**
     * Get the governorate that owns the city.
     */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
