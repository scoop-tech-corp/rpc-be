<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class promotion_discount_product extends Model
{
    protected $table = "promotion_discount_product";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoMasterId',
        'discountType', // percentOrAmount
        'productId',
        'amount',
        'percent',
        'totalMaxUsage',
        'maxUsagePerCustomer',
        'userId',
        'userUpdateId'
    ];
}
