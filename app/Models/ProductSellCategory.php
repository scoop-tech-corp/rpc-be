<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellCategory extends Model
{
    protected $table = "productSellCategories";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'productCategoryId', 'userId', 'userUpdateId'];
}
