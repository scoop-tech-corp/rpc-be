<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionFreeItem extends Model
{
    protected $table = "promotionFreeItems";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoMasterId',
        'quantityBuyItem',
        'productBuyId',
        'quantityFreeItem',
        'productFreeId',
        'totalMaxUsage',
        'maxUsagePerCustomer',
        'userId',
        'userUpdateId'
    ];
}
