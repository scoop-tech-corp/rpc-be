<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBundleDetail extends Model
{
    protected $table = "promotionBundleDetails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoBundleId',
        'productOrService',
        'percentOrAmount',
        'productType',
        'productId',
        'serviceId',
        'quantity',
        'userId',
        'userUpdateId'
    ];
}
