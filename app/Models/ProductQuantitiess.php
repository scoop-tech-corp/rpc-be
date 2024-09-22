<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQuantitiess extends Model
{
    protected $table = "productQuantities";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'fromQty', 'toQty', 'price', 'userId', 'userUpdateId'];
}
