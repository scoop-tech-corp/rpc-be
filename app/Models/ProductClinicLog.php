<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductClinicLog extends Model
{
    protected $table = "productClinicLogs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'transaction', 'remark',
    'quantity','balance', 'userId', 'userUpdateId'];
}
