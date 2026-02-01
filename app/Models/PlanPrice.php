<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    // اسم الجدول إذا لم يكن افتراضي
    protected $table = 'plan_price';

    // الأعمدة القابلة للكتابة (mass assignable)
    protected $fillable = [
        'plan_id',
        'location_id',
        'price',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }


    public function governorate()
    {
        return $this->belongsTo(Governorate::class,'location_id');
    }



}
