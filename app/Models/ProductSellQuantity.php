<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellQuantity extends Model
{
    protected $table = "productSellQuantities";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'fromQty', 'toQty', 'price', 'userId', 'userUpdateId'];
}
