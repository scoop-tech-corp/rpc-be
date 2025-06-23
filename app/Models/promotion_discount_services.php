<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class promotion_discount_services extends Model
{
    protected $table = "promotion_discount_services";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoMasterId',
        'discountType', // percentOrAmount
        'serviceId',
        'amount',
        'percent',
        'totalMaxUsage',
        'maxUsagePerCustomer',
        'userId',
        'userUpdateId'
    ];
}
