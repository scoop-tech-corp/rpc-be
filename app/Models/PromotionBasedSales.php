<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBasedSales extends Model
{
    protected $table = "promotionBasedSales";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoMasterId',
        'minPurchase',
        'maxPurchase',
        'percentOrAmount',
        'amount',
        'percent',
        'totalMaxUsage',
        'maxUsagePerCustomer',
        'userId',
        'userUpdateId'
    ];
}
