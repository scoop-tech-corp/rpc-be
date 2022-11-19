<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSell extends Model
{
    protected $table = "productSells";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['fullName', 'simpleName', 'sku',
        'productBrandId', 'productSupplierId', 'status','expiredDate', 'pricingStatus',
        'costPrice', 'marketPrice', 'price',
        'isShipped', 'weight', 'length', 'width',
        'height', 'introduction', 'description', 'userId', 'userUpdateId'];
}
