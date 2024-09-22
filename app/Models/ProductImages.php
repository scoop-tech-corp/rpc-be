<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImages extends Model
{
    protected $table = "productImages";

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'labelName', 'realImageName', 'imagePath', 'userId'];
}
