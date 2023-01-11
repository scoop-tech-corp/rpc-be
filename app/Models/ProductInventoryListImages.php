<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInventoryListImages extends Model
{
    protected $table = "productInventoryListImages";

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    protected $fillable = ['productInventoryListId', 'realImageName', 'imagePath', 'userId'];
}
