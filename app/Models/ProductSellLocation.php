<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellLocation extends Model
{
    protected $table = "productSellLocations";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'locationId', 'inStock', 'lowStock', 'userId', 'userUpdateId'];
}
