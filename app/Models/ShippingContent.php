<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShippingContent extends Model
{
    use HasFactory;
    protected $table = 'shipping_content';
    protected $fillable = [
        'name',
    ];

    /**
     * Get the clients for the shipping content.
     */
public function clients()
{
    return $this->belongsToMany(
        User::class,
        'client_shipping_content',
        'shipping_content_id', // العمود اللي بيشاور على الـ shipping content
        'client_id'            // العمود اللي بيشاور على الـ client
    );
}

}
