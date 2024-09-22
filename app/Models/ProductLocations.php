<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLocations extends Model
{
    protected $table = "productLocations";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'locationId', 'inStock', 'lowStock','reStockLimit','diffStock', 'userId', 'userUpdateId'];
}
