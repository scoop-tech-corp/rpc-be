<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCoreCategories extends Model
{
    protected $table = "productCoreCategories";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'productCategoryId', 'userId', 'userUpdateId'];
}
