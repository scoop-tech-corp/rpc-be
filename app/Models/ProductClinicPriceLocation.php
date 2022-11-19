<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicPriceLocation extends Model
{
    protected $table = "productClinicPriceLocations";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'locationId', 'price', 'userId', 'userUpdateId'];
}
