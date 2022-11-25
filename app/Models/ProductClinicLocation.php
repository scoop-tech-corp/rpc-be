<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicLocation extends Model
{
    protected $table = "productClinicLocations";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'locationId', 'inStock', 'lowStock','reStockLimit', 'userId', 'userUpdateId'];
}
