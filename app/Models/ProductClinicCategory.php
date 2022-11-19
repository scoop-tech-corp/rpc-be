<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicCategory extends Model
{
    protected $table = "productClinicCategories";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'productCategoryId', 'userId', 'userUpdateId'];
}
