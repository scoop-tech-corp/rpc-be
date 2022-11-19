<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicImages extends Model
{
    protected $table = "productClinicImages";

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'labelName', 'realImageName', 'imagePath', 'userId'];
}
