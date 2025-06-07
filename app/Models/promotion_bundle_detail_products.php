<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class promotion_bundle_detail_products extends Model
{
    protected $table = "promotion_bundle_detail_products";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoBundleId',
        'productId',
        'quantity',
        'userId',
        'userUpdateId'
    ];
}
