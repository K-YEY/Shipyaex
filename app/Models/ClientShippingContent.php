<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientShippingContent extends Model
{
    use HasFactory;

    protected $table = 'client_shipping_content'; // اسم الجدول

    protected $fillable = [
        'shipping_content_id',
        'client_id',
    ];

    /**
     * عNoقة مع Client (User)
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * عNoقة مع Shipping Content (ShippingContent)
     */
    public function shippingContent()
    {
        return $this->belongsTo(ShippingContent::class, 'shipping_content_id');
    }
}
