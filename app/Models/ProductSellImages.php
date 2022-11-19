<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellImages extends Model
{
    protected $table = "productSellImages";

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'labelName', 'realImageName', 'imagePath', 'userId'];
}
