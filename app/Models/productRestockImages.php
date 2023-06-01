<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockImages extends Model
{
    protected $table = "productRestockImages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productRestockDetailId','labelName','realImageName','imagePath','userId', 'userUpdateId'];
}
