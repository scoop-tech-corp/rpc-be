<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAdjustment extends Model
{
    protected $table = "productAdjustments";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'productType', 'adjustment', 'totalAdjustment', 'remark', 'userId', 'userUpdateId'];
}
