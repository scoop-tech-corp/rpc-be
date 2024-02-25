<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionCustomerGroup extends Model
{
    protected $table = "promotionCustomerGroups";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'promoMasterId',
        'customerGroupId',
        'userId',
        'userUpdateId'
    ];
}
