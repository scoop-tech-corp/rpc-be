<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellPriceLocation extends Model
{
    protected $table = "productSellPriceLocations";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'locationId', 'price', 'userId', 'userUpdateId'];
}
