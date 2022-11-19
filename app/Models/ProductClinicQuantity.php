<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicQuantity extends Model
{
    protected $table = "productClinicQuantities";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'fromQty', 'toQty', 'price', 'userId', 'userUpdateId'];
}
